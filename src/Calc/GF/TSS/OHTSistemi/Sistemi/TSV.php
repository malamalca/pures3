<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Sistemi;

use App\Calc\GF\TSS\OHTSistemi\Izbire\VrstaRezima;
use App\Calc\GF\TSS\OHTSistemi\OHTSistem;
use App\Calc\GF\TSS\TSSInterface;
use App\Calc\GF\TSS\TSSVrstaEnergenta;

class TSV extends TSSInterface
{
    // Excel ima 2 iteraciji za TSV
    public int $stevilo_iteracij = 2;

    public VrstaRezima $rezim;

    /**
     * @var array $razvodi Seznam razvodov iz OHT, ki sodelujejo pri TSV
     */
    public array $razvodi = [];
    public array $prenosniki = [];
    public array $hranilniki = [];
    public array $generatorji = [];

    public array $energijaPoEnergentih = [];
    public array $potrebnaEnergija = [];

    public array $vracljiveIzgubeVOgrevanje = [];
    public array $vracljiveIzgubeVTSV = [];

    /**
     * Class Constructor
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function __construct($config = null)
    {
        if ($config) {
            $this->parseConfig($config);
        }
    }

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->id = $config->id ?? null;
        $this->rezim = VrstaRezima::from($config->rezim ?? null);

        $this->stevilo_iteracij = $config->steviloIteracij ?? 2;

        $this->razvodi = $config->razvodi ?? [];
        $this->prenosniki = $config->prenosniki ?? [];
        $this->hranilniki = $config->hranilniki ?? [];
        $this->generatorji = $config->generatorji ?? [];
    }

    /**
     * Analiza sistema
     *
     * @param array $toplotneIzgube Potrebna energija predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function analiza($toplotneIzgube, OHTSistem $sistem, $cona, $okolje, $params = [])
    {
        $this->vracljiveIzgubeVTSV = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

        for ($i = 0; $i < $this->stevilo_iteracij; $i++) {
            $this->potrebnaEnergija = array_subtract_values($cona->energijaTSV, $this->vracljiveIzgubeVTSV);
            $this->potrebnaElektricnaEnergija = [];
            $this->obnovljivaEnergija = [];
            $this->vracljiveIzgube = [];
            $this->vracljiveIzgubeVOgrevanje = [];

            foreach ($this->razvodi as $razvodId) {
                $razvod = array_first_callback($sistem->razvodi, fn($r) => $r->id == $razvodId);
                if (!$razvod) {
                    throw new \Exception(sprintf('Razvod TSV "%s" ne obstaja', $razvodId));
                }

                $razvod->analiza($this->potrebnaEnergija, $sistem, $cona, $okolje, ['namen' => 'tsv']);

                $this->potrebnaEnergija =
                    array_sum_values($this->potrebnaEnergija, $razvod->toplotneIzgube['tsv']);

                $this->potrebnaElektricnaEnergija =
                    array_sum_values($this->potrebnaElektricnaEnergija, $razvod->potrebnaElektricnaEnergija['tsv']);

                $this->vracljiveIzgubeVOgrevanje =
                    array_sum_values($this->vracljiveIzgubeVOgrevanje, $razvod->vracljiveIzgube['tsv']);
                $this->vracljiveIzgubeVOgrevanje =
                    array_sum_values($this->vracljiveIzgubeVOgrevanje, $razvod->vracljiveIzgubeAux['tsv']);

                $this->vracljiveIzgubeVTSV =
                    array_sum_values($this->vracljiveIzgubeVTSV, $razvod->vracljiveIzgubeTSV['tsv']);
            }

            foreach ($this->hranilniki as $hranilnikId) {
                $hranilnik = array_first_callback($sistem->hranilniki, fn($hranilnik) => $hranilnik->id == $hranilnikId);
                if (!$hranilnik) {
                    throw new \Exception(sprintf('Hranilnik TSV "%s" ne obstaja', $hranilnikId));
                }

                $hranilnik->analiza($this->potrebnaEnergija, $sistem, $cona, $okolje, ['namen' => 'tsv']);

                $this->potrebnaEnergija =
                    array_sum_values($this->potrebnaEnergija, $hranilnik->toplotneIzgube['tsv']);

                $this->potrebnaElektricnaEnergija =
                    array_sum_values(
                        $this->potrebnaElektricnaEnergija,
                        $hranilnik->potrebnaElektricnaEnergija['tsv'] ?? []
                    );

                $this->vracljiveIzgubeVOgrevanje =
                    array_sum_values($this->vracljiveIzgubeVOgrevanje, $hranilnik->vracljiveIzgube['tsv'] ?? []);
                $this->vracljiveIzgubeVOgrevanje =
                    array_sum_values($this->vracljiveIzgubeVOgrevanje, $hranilnik->vracljiveIzgubeAux['tsv'] ?? []);

                $this->vracljiveIzgubeVTSV =
                    array_sum_values($this->vracljiveIzgubeVTSV, $hranilnik->vracljiveIzgubeTSV['tsv'] ?? []);
            }

            // generator lahko pokrije le del energije (npr solarni kolektroji); ostali generatorji pokrijejo ostalo
            $nepokritaEnergija = $this->potrebnaEnergija;

            foreach ($this->generatorji as $generatorId) {
                $generator = array_first_callback($sistem->generatorji, fn($g) => $g->id == $generatorId);
                if (!$generator) {
                    throw new \Exception(sprintf('Generator "%s" ne obstaja', $generatorId));
                }

                // kadar je več iteracij se vracljive izgube upoštevajo, kadar pa je samo ena se pa ne, zato
                // je potreeben še en korak
                if ($this->stevilo_iteracij == 1) {
                    //$nepokritaEnergija = array_subtract_values($nepokritaEnergija, $this->vracljiveIzgubeVTSV);
                }

                $generator->analiza($nepokritaEnergija, $sistem, $cona, $okolje, ['namen' => 'tsv']);

                $nepokritaEnergija = $generator->nepokritaEnergija['tsv'] ?? [];

                $this->potrebnaEnergija = array_sum_values($this->potrebnaEnergija, $generator->toplotneIzgube['tsv']);

                $this->obnovljivaEnergija =
                    array_sum_values($this->obnovljivaEnergija, $generator->obnovljivaEnergija['tsv']);

                $this->potrebnaElektricnaEnergija = array_sum_values(
                    $this->potrebnaElektricnaEnergija,
                    $generator->potrebnaElektricnaEnergija['tsv']
                );

                $this->vracljiveIzgubeVOgrevanje =
                    array_sum_values($this->vracljiveIzgubeVOgrevanje, $generator->vracljiveIzgube['tsv'] ?? []);
                $this->vracljiveIzgubeVOgrevanje =
                    array_sum_values($this->vracljiveIzgubeVOgrevanje, $generator->vracljiveIzgubeAux['tsv'] ?? []);

                $this->vracljiveIzgubeVTSV =
                    array_sum_values($this->vracljiveIzgubeVTSV, $generator->vracljiveIzgubeTSV['tsv'] ?? []);
            }
        }

        $this->potrebnaEnergija = array_map(
            fn($e) => $e / $sistem->energent->maksimalniIzkoristek(),
            $this->potrebnaEnergija
        );

        $dejanskaEnergija = array_subtract_values($this->potrebnaEnergija, $this->obnovljivaEnergija);
        $this->energijaPoEnergentih[$sistem->energent->value] = array_sum($dejanskaEnergija);

        $elektricnaEnergija = array_sum($this->potrebnaElektricnaEnergija);
        if (count($this->potrebnaElektricnaEnergija) > 0 && $elektricnaEnergija != 0) {
            $this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] =
                ($this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] ?? 0) +
                $elektricnaEnergija;
        }

        $obnovljivaEnergija = array_sum($this->obnovljivaEnergija);
        if (count($this->obnovljivaEnergija) > 0 && $obnovljivaEnergija != 0) {
            $this->energijaPoEnergentih[TSSVrstaEnergenta::Okolje->value] =
                ($this->energijaPoEnergentih[TSSVrstaEnergenta::Okolje->value] ?? 0) +
                $obnovljivaEnergija;
        }
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $sistem = parent::export();
        $sistem->razvodi = $this->razvodi;
        $sistem->prenosniki = $this->prenosniki;
        $sistem->hranilniki = $this->hranilniki;
        $sistem->generatorji = $this->generatorji;

        $sistem->energijaPoEnergentih = $this->energijaPoEnergentih;

        return $sistem;
    }
}
