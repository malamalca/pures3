<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi;

use App\Calc\GF\Cone\Cona;
use App\Calc\GF\TSS\OgrevalniSistemi\Izbire\VrstaRezima;
use App\Calc\GF\TSS\TSSVrstaEnergenta;
use App\Lib\Calc;

class ToplovodniOgrevalniSistem extends OgrevalniSistem
{
    // Excel ima 4 iteracije
    private const STEVILO_ITERACIJ = 4;

    public string $namen;

    public ?\stdClass $tsv;
    public ?\stdClass $ogrevanje;

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    protected function parseConfig($config)
    {
        parent::parseConfig($config);

        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->tsv = $config->tsv ?? null;
        if ($this->tsv && !empty($config->tsv->rezim)) {
            $this->tsv->rezim = VrstaRezima::from($config->tsv->rezim);
        }

        $this->ogrevanje = $config->ogrevanje ?? null;
        if ($this->ogrevanje && !empty($config->ogrevanje->rezim)) {
            $this->ogrevanje->rezim = VrstaRezima::from($config->ogrevanje->rezim);
        }
    }

    /**
     * Inicializacija parametrov sistema
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public function init($cona, $okolje)
    {
        $this->standardnaMoc = ($cona->specTransmisijskeIzgube + $cona->specVentilacijskeIzgube) *
            ($cona->notranjaTOgrevanje - $okolje->projektnaZunanjaT) / 1000;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = 24 * $stDni;

            // betaH - Izračun povprečnih obremenitev podsistemov
            $this->povprecnaObremenitev[$mesec] = $cona->energijaOgrevanje[$mesec] / ($this->standardnaMoc * $stUr);
        }
    }

    /**
     * Glavna metoda za analizo TSV
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public function analizaTSV($cona, $okolje)
    {
        $this->tsv->potrebnaEnergija = $cona->energijaTSV;
        $this->tsv->potrebnaElektricnaEnergija = [];
        $this->tsv->obnovljivaEnergija = [];
        $this->vracljiveIzgubeVOgrevanje = [];
        $this->tsv->vneseneIzgube = [];

        if (isset($this->tsv->razvodi)) {
            foreach ($this->tsv->razvodi as $razvodId) {
                $razvod = array_first($this->razvodi, fn($r) => $r->id == $razvodId);
                if (!$razvod) {
                    throw new \Exception(sprintf('Razvod TSV "%s" ne obstaja', $razvodId));
                }

                $razvod->analiza([], $this, $cona, $okolje);

                $this->tsv->potrebnaEnergija = array_sum_values($this->tsv->potrebnaEnergija, $razvod->toplotneIzgube);
                $this->tsv->potrebnaElektricnaEnergija =
                    array_sum_values($this->tsv->potrebnaElektricnaEnergija, $razvod->potrebnaElektricnaEnergija);

                $this->vracljiveIzgubeVOgrevanje =
                    array_sum_values($this->vracljiveIzgubeVOgrevanje, $razvod->vracljiveIzgube);
                $this->vracljiveIzgubeVOgrevanje =
                    array_sum_values($this->vracljiveIzgubeVOgrevanje, $razvod->vracljiveIzgubeAux);

                $this->tsv->potrebnaEnergija =
                    array_subtract_values($this->tsv->potrebnaEnergija, $razvod->vracljiveIzgubeAux);
            }
        }

        if (isset($this->tsv->hranilniki)) {
            foreach ($this->tsv->hranilniki as $hranilnikId) {
                $hranilnik = array_first($this->hranilniki, fn($hranilnik) => $hranilnik->id == $hranilnikId);
                if (!$hranilnik) {
                    throw new \Exception(sprintf('Hranilnik TSV "%s" ne obstaja', $hranilnikId));
                }

                $hranilnik->analiza([], $this, $cona, $okolje);

                $this->tsv->potrebnaEnergija =
                    array_sum_values($this->tsv->potrebnaEnergija, $hranilnik->toplotneIzgube);
                $this->vracljiveIzgubeVOgrevanje =
                    array_sum_values($this->vracljiveIzgubeVOgrevanje, $hranilnik->vracljiveIzgube);
            }
        }

        foreach ($this->tsv->generatorji as $generatorId) {
            $generator = array_first($this->generatorji, fn($g) => $g->id == $generatorId);
            if (!$generator) {
                throw new \Exception(sprintf('Generator "%s" ne obstaja', $generatorId));
            }

            $generator->analiza(
                $this->tsv->potrebnaEnergija,
                $this,
                $cona,
                $okolje,
                ['namen' => 'tsv', 'rezim' => $this->tsv->rezim]
            );

            $this->tsv->obnovljivaEnergija =
                array_sum_values($this->tsv->obnovljivaEnergija, $generator->obnovljivaEnergija['tsv']);
            $this->tsv->potrebnaElektricnaEnergija =
                array_sum_values($this->tsv->potrebnaElektricnaEnergija, $generator->potrebnaElektricnaEnergija['tsv']);
        }
    }

    /**
     * Glavna metoda za analizo ogrevanja
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public function analizaOgrevanja($cona, $okolje)
    {
        $vracljiveIzgube = $this->vracljiveIzgubeVOgrevanje;

        // iteracija za vračljive izgube
        for ($i = 0; $i < self::STEVILO_ITERACIJ; $i++) {
            // ponovno poračunam potrebno energijo za ogrevanje
            $spremembaCone = new Cona(null, $cona);
            $spremembaCone->vracljiveIzgube = $vracljiveIzgube;
            $spremembaCone->izracunFaktorjaIzkoristka();
            $spremembaCone->izracunEnergijeOgrevanjeHlajanje();
            $cona = $spremembaCone->export();
            $this->init($cona, $okolje);

            $vracljiveIzgube = $this->vracljiveIzgubeVOgrevanje;

            $this->ogrevanje->potrebnaEnergija = $cona->energijaOgrevanje;
            $this->ogrevanje->potrebnaElektricnaEnergija = [];
            $this->ogrevanje->obnovljivaEnergija = [];

            if (isset($this->ogrevanje->prenosniki)) {
                foreach ($this->ogrevanje->prenosniki as $prenosnikId) {
                    $prenosnik = array_first($this->koncniPrenosniki, fn($p) => $p->id == $prenosnikId);
                    if (!$prenosnik) {
                        throw new \Exception(sprintf('Prenosnik ogrevanja "%s" ne obstaja', $prenosnikId));
                    }

                    $prenosnik->analiza($this->ogrevanje->potrebnaEnergija, $this, $cona, $okolje);

                    $this->ogrevanje->potrebnaEnergija =
                        array_sum_values($this->ogrevanje->potrebnaEnergija, $prenosnik->toplotneIzgube);
                    $this->ogrevanje->potrebnaElektricnaEnergija = array_sum_values(
                        $this->ogrevanje->potrebnaElektricnaEnergija,
                        $prenosnik->potrebnaElektricnaEnergija
                    );

                    $vracljiveIzgube = array_sum_values($vracljiveIzgube, $prenosnik->vracljiveIzgube);
                    $vracljiveIzgube = array_sum_values($vracljiveIzgube, $prenosnik->vracljiveIzgubeAux);
                }
            }

            if (isset($this->ogrevanje->razvodi)) {
                foreach ($this->ogrevanje->razvodi as $razvodId) {
                    $razvod = array_first($this->razvodi, fn($r) => $r->id == $razvodId);
                    if (!$razvod) {
                        throw new \Exception(sprintf('Razvod ogrevanja "%s" ne obstaja', $razvodId));
                    }

                    $prenosnik = array_first($this->koncniPrenosniki, fn($p) => $p->id == $razvod->idPrenosnika);

                    $razvod->analiza(
                        $this->ogrevanje->potrebnaEnergija,
                        $this,
                        $cona,
                        $okolje,
                        ['prenosnik' => $prenosnik, 'rezim' => $this->ogrevanje->rezim]
                    );

                    $this->ogrevanje->potrebnaEnergija =
                        array_sum_values($this->ogrevanje->potrebnaEnergija, $razvod->toplotneIzgube);
                    $this->ogrevanje->potrebnaElektricnaEnergija =
                        array_sum_values(
                            $this->ogrevanje->potrebnaElektricnaEnergija,
                            $razvod->potrebnaElektricnaEnergija
                        );

                    $vracljiveIzgube = array_sum_values($vracljiveIzgube, $razvod->vracljiveIzgube);
                    $vracljiveIzgube = array_sum_values($vracljiveIzgube, $razvod->vracljiveIzgubeAux);
                }
            }

            if (isset($this->ogrevanje->hranilniki)) {
                foreach ($this->ogrevanje->hranilniki as $hranilnikId) {
                    $hranilnik = array_first($this->hranilniki, fn($hranilnik) => $hranilnik->id == $hranilnikId);
                    if (!$hranilnik) {
                        throw new \Exception(sprintf('Hranilnik ogrevanja "%s" ne obstaja', $hranilnikId));
                    }

                    $hranilnik->analiza([], $this, $cona, $okolje);
                    $this->ogrevanje->potrebnaEnergija =
                        array_sum_values($this->ogrevanje->potrebnaEnergija, $hranilnik->toplotneIzgube);

                    $vracljiveIzgube = array_sum_values($vracljiveIzgube, $hranilnik->vracljiveIzgube);
                    $vracljiveIzgube = array_sum_values($vracljiveIzgube, $hranilnik->vracljiveIzgubeAux);
                }
            }

            foreach ($this->ogrevanje->generatorji as $generatorId) {
                $generator = array_first($this->generatorji, fn($g) => $g->id == $generatorId);
                if (!$generator) {
                    throw new \Exception(sprintf('Generator "%s" ne obstaja', $generatorId));
                }

                $generator->analiza(
                    $this->ogrevanje->potrebnaEnergija,
                    $this,
                    $cona,
                    $okolje,
                    ['namen' => 'ogrevanje', 'rezim' => $this->ogrevanje->rezim]
                );

                $vracljiveIzgube = array_sum_values($vracljiveIzgube, $generator->vracljiveIzgube ?? []);
                $vracljiveIzgube = array_sum_values($vracljiveIzgube, $generator->vracljiveIzgubeAux ?? []);
            }
        }

        // seštejem še obnovljivo energijo in skupno potrebno električno energijo
        foreach ($this->ogrevanje->generatorji as $generatorId) {
            $generator = array_first($this->generatorji, fn($g) => $g->id == $generatorId);
            if (!$generator) {
                throw new \Exception(sprintf('Generator "%s" ne obstaja', $generatorId));
            }

            if (!empty($generator->potrebnaEnergija['ogrevanje'])) {
                $this->ogrevanje->potrebnaEnergija =
                    array_sum_values($this->ogrevanje->potrebnaEnergija, $generator->potrebnaEnergija['ogrevanje']);
            } else {
                $this->ogrevanje->potrebnaEnergija =
                    array_sum_values($this->ogrevanje->potrebnaEnergija, $generator->potrebnaEnergija);
            }

            $this->ogrevanje->potrebnaEnergija = array_map(
                fn($e) => $e / $this->energent->maksimalniIzkoristek(),
                $this->ogrevanje->potrebnaEnergija
            );

            if (!empty($generator->obnovljivaEnergija['ogrevanje'])) {
                $this->ogrevanje->obnovljivaEnergija =
                    array_sum_values($this->ogrevanje->obnovljivaEnergija, $generator->obnovljivaEnergija['ogrevanje']);
            }
            if (!empty($generator->potrebnaElektricnaEnergija['ogrevanje'])) {
                $this->ogrevanje->potrebnaElektricnaEnergija = array_sum_values(
                    $this->ogrevanje->potrebnaElektricnaEnergija,
                    $generator->potrebnaElektricnaEnergija['ogrevanje']
                );
            }
        }
    }

    /**
     * Glavna metoda za analizo ogrevalnega sistema
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public function analiza($cona, $okolje)
    {
        $this->init($cona, $okolje);

        $energijaPoEnergentihTemplate = [];
        $energijaPoEnergentihTemplate[TSSVrstaEnergenta::Elektrika->value] = 0;
        $energijaPoEnergentihTemplate[TSSVrstaEnergenta::Okolje->value] = 0;
        if ($this->energent != TSSVrstaEnergenta::Elektrika) {
            $energijaPoEnergentihTemplate[$this->energent->value] = 0;
        }

        $this->energijaPoEnergentih = $energijaPoEnergentihTemplate;
        $this->energijaPoEnergentihOgrevanje = $energijaPoEnergentihTemplate;
        $this->energijaPoEnergentihTSV = $energijaPoEnergentihTemplate;
        $this->energijaPoEnergentihHlajenje = $energijaPoEnergentihTemplate;

        $this->potrebnaEnergija = [];
        $this->potrebnaElektricnaEnergija = [];
        $this->obnovljivaEnergija = [];

        $this->podsistemi = [];

        $skupnaDovedenaEnergijaOgrHlaTsv = 0;
        $utezenaDovedenaEnergijaOgrHlaTsv = 0;

        // najprej analiziram toplo vodo
        if (!empty($this->tsv)) {
            $skupnaDovedenaEnergijaOgrHlaTsv += $cona->skupnaEnergijaTSV;

            $this->analizaTSV($cona, $okolje);

            $this->potrebnaEnergija = array_sum_values($this->potrebnaEnergija, $this->tsv->potrebnaEnergija);
            $this->potrebnaElektricnaEnergija =
                array_sum_values($this->potrebnaElektricnaEnergija, $this->tsv->potrebnaElektricnaEnergija);

            $this->obnovljivaEnergija =
                array_sum_values($this->obnovljivaEnergija, $this->tsv->obnovljivaEnergija);

            $dejanskaEnergija = array_subtract_values($this->tsv->potrebnaEnergija, $this->tsv->obnovljivaEnergija);

            $this->energijaPoEnergentihTSV[$this->energent->value] +=
                array_sum($dejanskaEnergija);
            $this->energijaPoEnergentihTSV[TSSVrstaEnergenta::Elektrika->value] +=
                array_sum($this->tsv->potrebnaElektricnaEnergija);
            $this->energijaPoEnergentihTSV[TSSVrstaEnergenta::Okolje->value] +=
                array_sum($this->tsv->obnovljivaEnergija);

            $this->energijaPoEnergentih[$this->energent->value] +=
                array_sum($dejanskaEnergija);
            $this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] +=
                array_sum($this->tsv->potrebnaElektricnaEnergija);
            $this->energijaPoEnergentih[TSSVrstaEnergenta::Okolje->value] +=
                array_sum($this->tsv->obnovljivaEnergija);

            $utezenaDovedenaEnergijaOgrHlaTsv +=
                array_sum($dejanskaEnergija) * $this->energent->utezniFaktor('tot') +
                array_sum($this->tsv->potrebnaElektricnaEnergija) * TSSVrstaEnergenta::Elektrika->utezniFaktor('tot') +
                array_sum($this->tsv->obnovljivaEnergija) * TSSVrstaEnergenta::Okolje->utezniFaktor('tot');
        }

        // potem ogrevanje
        if (!empty($this->ogrevanje)) {
            $skupnaDovedenaEnergijaOgrHlaTsv += $cona->skupnaEnergijaOgrevanje;

            $this->analizaOgrevanja($cona, $okolje);

            $this->potrebnaEnergija = array_sum_values($this->potrebnaEnergija, $this->ogrevanje->potrebnaEnergija);

            $this->potrebnaElektricnaEnergija =
                array_sum_values($this->potrebnaElektricnaEnergija, $this->ogrevanje->potrebnaElektricnaEnergija);

            $this->obnovljivaEnergija =
                array_sum_values($this->obnovljivaEnergija, $this->ogrevanje->obnovljivaEnergija);

            $dejanskaEnergija = array_subtract_values(
                $this->ogrevanje->potrebnaEnergija,
                $this->ogrevanje->obnovljivaEnergija
            );

            $this->energijaPoEnergentih[$this->energent->value] +=
                array_sum($dejanskaEnergija);
            $this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] +=
                array_sum($this->ogrevanje->potrebnaElektricnaEnergija);
            $this->energijaPoEnergentih[TSSVrstaEnergenta::Okolje->value] +=
                array_sum($this->ogrevanje->obnovljivaEnergija);

            $this->energijaPoEnergentihOgrevanje[$this->energent->value] +=
                array_sum($dejanskaEnergija);
            $this->energijaPoEnergentihOgrevanje[TSSVrstaEnergenta::Elektrika->value] +=
                array_sum($this->ogrevanje->potrebnaElektricnaEnergija);
            $this->energijaPoEnergentihOgrevanje[TSSVrstaEnergenta::Okolje->value] +=
                array_sum($this->ogrevanje->obnovljivaEnergija);

            $utezenaDovedenaEnergijaOgrHlaTsv +=
                array_sum($dejanskaEnergija) * $this->energent->utezniFaktor('tot') +
                array_sum($this->ogrevanje->potrebnaElektricnaEnergija) *
                TSSVrstaEnergenta::Elektrika->utezniFaktor('tot') +
                array_sum($this->ogrevanje->obnovljivaEnergija) * TSSVrstaEnergenta::Okolje->utezniFaktor('tot');
        }

        $this->letnaUcinkovitostOgrHlaTsv = $skupnaDovedenaEnergijaOgrHlaTsv / $utezenaDovedenaEnergijaOgrHlaTsv;
        $this->minLetnaUcinkovitostOgrHlaTsv = $this->energent->minimalniIzkoristekOgrHlaTsv();
    }
}
