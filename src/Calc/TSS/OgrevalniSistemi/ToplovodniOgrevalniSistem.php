<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi;

use App\Calc\TSS\EnergentFactory;
use App\Calc\TSS\OgrevalniSistemi\Izbire\VrstaRezima;
use App\Calc\TSS\TSSVrstaEnergenta;
use App\Lib\Calc;
use App\Lib\CalcCone;

class ToplovodniOgrevalniSistem extends OgrevalniSistem
{
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

        $this->energent = EnergentFactory::create($config->energent ?? 'default');

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
     * @return void
     */
    public function init($cona)
    {
        $this->standardnaMoc = ($cona->specTransmisijskeIzgube + $cona->specVentilacijskeIzgube) *
            ($cona->notranjaTOgrevanje - $cona->zunanjaT) / 1000;

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
        $this->tsv->potrebnaEnergija = $cona->potrebaTSV;
        $this->tsv->potrebnaElektricnaEnergija = [];
        $this->tsv->obnovljivaEnergija = [];
        $this->tsv->vracljiveIzgubeVOgrevanje = [];
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

                $this->tsv->vracljiveIzgubeVOgrevanje =
                    array_sum_values($this->tsv->vracljiveIzgubeVOgrevanje, $razvod->vracljiveIzgube);
                $this->tsv->vracljiveIzgubeVOgrevanje =
                    array_sum_values($this->tsv->vracljiveIzgubeVOgrevanje, $razvod->vracljiveIzgubeAux);

                // TODO: vračljive izgube v sistem morajo imeti svoj property v classu
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
                $this->tsv->vracljiveIzgubeVOgrevanje =
                    array_sum_values($this->tsv->vracljiveIzgubeVOgrevanje, $hranilnik->vracljiveIzgube);
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
        if (!empty($this->tsv->vracljiveIzgubeVOgrevanje)) {
            // ponovno poračunam potrebno energijo za ogrevanje
            $cona->vracljiveIzgube = $this->tsv->vracljiveIzgubeVOgrevanje;
            CalcCone::izracunFaktorjaIzkoristka($cona, $okolje);
            CalcCone::izracunEnergijeOgrevanjeHlajanje($cona, $okolje);
            $this->init($cona);
        }
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
                    array_sum_values($this->ogrevanje->potrebnaElektricnaEnergija, $razvod->potrebnaElektricnaEnergija);

                // TODO: vračljive izgube v sistem morajo imeti svoj property v classu
                // $this->ogrevanje->potrebnaEnergija = array_subtract_values($this->ogrevanje->potrebnaEnergija, $razvod->vracljiveIzgubeAux);
                // Se res ne upošteva energija Aux kot vrnjena???
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
            }
        }

        foreach ($this->ogrevanje->generatorji as $generatorId) {
            $generator = array_first($this->generatorji, fn($g) => $g->id == $generatorId);
            if (!$generator) {
                throw new \Exception(sprintf('Generator "%s" ne obstaja', $generatorId));
            }

            //$generator->potrebnaEnergija = [];
            //$generator->potrebnaElektricnaEnergija = [];

            $generator->analiza(
                $this->ogrevanje->potrebnaEnergija,
                $this,
                $cona,
                $okolje,
                ['namen' => 'ogrevanje', 'rezim' => $this->ogrevanje->rezim]
            );

            $this->ogrevanje->obnovljivaEnergija =
                array_sum_values($this->ogrevanje->obnovljivaEnergija, $generator->obnovljivaEnergija['ogrevanje']);
            $this->ogrevanje->potrebnaElektricnaEnergija = array_sum_values(
                $this->ogrevanje->potrebnaElektricnaEnergija,
                $generator->potrebnaElektricnaEnergija['ogrevanje']
            );
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
        $this->init($cona);

        $this->energijaPoEnergentih = [];
        $this->potrebnaEnergija = [];
        $this->potrebnaElektricnaEnergija = [];
        $this->obnovljivaEnergija = [];

        $this->podsistemi = [];

        // najprej analiziram toplo vodo
        if (!empty($this->tsv)) {
            $this->analizaTSV($cona, $okolje);

            $this->potrebnaEnergija = array_sum_values($this->potrebnaEnergija, $this->tsv->potrebnaEnergija);
            $this->potrebnaElektricnaEnergija =
                array_sum_values($this->potrebnaElektricnaEnergija, $this->tsv->potrebnaElektricnaEnergija);

            $this->obnovljivaEnergija =
                array_sum_values($this->obnovljivaEnergija, $this->tsv->obnovljivaEnergija);

            $dejanskaEnergija = array_subtract_values($this->tsv->potrebnaEnergija, $this->tsv->obnovljivaEnergija);

            $this->energijaPoEnergentih['tsv'][TSSVrstaEnergenta::Elektrika->value] =
                ($this->energijaPoEnergentih['tsv'][TSSVrstaEnergenta::Elektrika->value] ?? 0) +
                array_sum($dejanskaEnergija) +
                array_sum($this->tsv->potrebnaElektricnaEnergija);

            $this->energijaPoEnergentih['tsv'][TSSVrstaEnergenta::Okolje->value] =
                ($this->energijaPoEnergentih['tsv'][TSSVrstaEnergenta::Okolje->value] ?? 0) +
                array_sum($this->tsv->obnovljivaEnergija);
        }

        // potem ogrevanje
        if (!empty($this->ogrevanje)) {
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
            $this->energijaPoEnergentih['ogrevanje'][TSSVrstaEnergenta::Elektrika->value] =
                ($this->energijaPoEnergentih['ogrevanje'][TSSVrstaEnergenta::Elektrika->value] ?? 0) +
                array_sum($dejanskaEnergija) +
                array_sum($this->ogrevanje->potrebnaElektricnaEnergija);

            $this->energijaPoEnergentih['ogrevanje'][TSSVrstaEnergenta::Okolje->value] =
                ($this->energijaPoEnergentih['ogrevanje'][TSSVrstaEnergenta::Okolje->value] ?? 0) +
                array_sum($this->ogrevanje->obnovljivaEnergija);
        }
    }
}
