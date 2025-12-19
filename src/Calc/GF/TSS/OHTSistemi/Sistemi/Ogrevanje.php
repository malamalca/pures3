<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Sistemi;

use App\Calc\GF\Cone\Cona;
use App\Calc\GF\TSS\OHTSistemi\Izbire\VrstaRezima;
use App\Calc\GF\TSS\OHTSistemi\OHTSistem;
use App\Calc\GF\TSS\TSSInterface;
use App\Calc\GF\TSS\TSSVrstaEnergenta;

class Ogrevanje extends TSSInterface
{
    // Excel ima 4 iteracije
    private const STEVILO_ITERACIJ = 4;

    public ?VrstaRezima $rezim;

    /**
     * @var array $razvodi Seznam razvodov iz OHT, ki sodelujejo pri ogrevanju
     */
    public array $razvodi = [];
    public array $prenosniki = [];
    public array $hranilniki = [];
    public array $generatorji = [];

    public array $energijaPoEnergentih = [];
    public array $potrebnaEnergija = [];
    public array $vrnjeneIzgubeVOgrevanje = [];

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

        $this->id = $config->id ?? null;

        $this->razvodi = $config->razvodi ?? [];
        $this->prenosniki = $config->prenosniki ?? [];
        $this->hranilniki = $config->hranilniki ?? [];
        $this->generatorji = $config->generatorji ?? [];
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
        $this->rezim = !empty($config->rezim) ? VrstaRezima::from($config->rezim) : null;

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
        $vracljiveIzgube = $this->vrnjeneIzgubeVOgrevanje;

        // iteracija za vračljive izgube
        for ($i = 0; $i < self::STEVILO_ITERACIJ; $i++) {
            // ponovno poračunam potrebno energijo za ogrevanje
            $spremembaCone = new Cona(null, $cona);
            $spremembaCone->vrnjeneIzgubeVOgrevanje = $vracljiveIzgube;
            $spremembaCone->izracunFaktorjaIzkoristka();
            $spremembaCone->izracunEnergijeOgrevanjeHlajanje();
            $cona = $spremembaCone->export();

            $vracljiveIzgube = $this->vrnjeneIzgubeVOgrevanje;

            $this->potrebnaEnergija = $cona->energijaOgrevanje;
            $this->potrebnaElektricnaEnergija = [];
            $this->obnovljivaEnergija = [];

            foreach ($this->prenosniki as $prenosnikId) {
                $prenosnik = array_first_callback($sistem->koncniPrenosniki, fn($p) => $p->id == $prenosnikId);
                if (!$prenosnik) {
                    throw new \Exception(sprintf('Prenosnik ogrevanja "%s" ne obstaja', $prenosnikId));
                }

                $prenosnik->analiza($this->potrebnaEnergija, $sistem, $cona, $okolje, ['namen' => 'ogrevanje']);

                $this->potrebnaEnergija =
                    array_sum_values($this->potrebnaEnergija, $prenosnik->toplotneIzgube['ogrevanje'] ?? []);
                $this->potrebnaElektricnaEnergija =
                    array_sum_values(
                        $this->potrebnaElektricnaEnergija,
                        $prenosnik->potrebnaElektricnaEnergija['ogrevanje'] ?? []
                    );

                $vracljiveIzgube =
                    array_sum_values($vracljiveIzgube, $prenosnik->vracljiveIzgube['ogrevanje'] ?? []);
                $vracljiveIzgube =
                    array_sum_values($vracljiveIzgube, $prenosnik->vracljiveIzgubeAux['ogrevanje'] ?? []);
            }

            foreach ($this->razvodi as $razvodId) {
                $razvod = array_first_callback($sistem->razvodi, fn($r) => $r->id == $razvodId);
                if (!$razvod) {
                    throw new \Exception(sprintf('Razvod ogrevanja "%s" ne obstaja', $razvodId));
                }

                $razvod->analiza($this->potrebnaEnergija, $sistem, $cona, $okolje, ['namen' => 'ogrevanje']);

                $this->potrebnaEnergija =
                    array_sum_values($this->potrebnaEnergija, $razvod->toplotneIzgube['ogrevanje'] ?? []);
                $this->potrebnaElektricnaEnergija =
                    array_sum_values(
                        $this->potrebnaElektricnaEnergija,
                        $razvod->potrebnaElektricnaEnergija['ogrevanje'] ?? []
                    );

                $vracljiveIzgube = array_sum_values($vracljiveIzgube, $razvod->vracljiveIzgube['ogrevanje'] ?? []);
                $vracljiveIzgube = array_sum_values($vracljiveIzgube, $razvod->vracljiveIzgubeAux['ogrevanje'] ?? []);
            }

            foreach ($this->hranilniki as $hranilnikId) {
                $hranilnik = array_first_callback($sistem->hranilniki, fn($hranilnik) => $hranilnik->id == $hranilnikId);
                if (!$hranilnik) {
                    throw new \Exception(sprintf('Hranilnik ogrevanja "%s" ne obstaja', $hranilnikId));
                }

                $hranilnik->analiza([], $sistem, $cona, $okolje, ['namen' => 'ogrevanje']);
                $this->potrebnaEnergija =
                    array_sum_values($this->potrebnaEnergija, $hranilnik->toplotneIzgube['ogrevanje']);
                $this->potrebnaElektricnaEnergija =
                    array_sum_values(
                        $this->potrebnaElektricnaEnergija,
                        $hranilnik->potrebnaElektricnaEnergija['ogrevanje']
                    );

                $vracljiveIzgube = array_sum_values($vracljiveIzgube, $hranilnik->vracljiveIzgube['ogrevanje']);
                $vracljiveIzgube = array_sum_values($vracljiveIzgube, $hranilnik->vracljiveIzgubeAux['ogrevanje']);
            }

            foreach ($this->generatorji as $generatorId) {
                $generator = array_first_callback($sistem->generatorji, fn($g) => $g->id == $generatorId);
                if (!$generator) {
                    throw new \Exception(sprintf('Generator "%s" ne obstaja', $generatorId));
                }

                $generator->analiza($this->potrebnaEnergija, $sistem, $cona, $okolje, ['namen' => 'ogrevanje']);

                $this->potrebnaEnergija =
                    array_sum_values($this->potrebnaEnergija, $generator->toplotneIzgube['ogrevanje']);

                $this->potrebnaElektricnaEnergija = array_sum_values(
                    $this->potrebnaElektricnaEnergija,
                    $generator->potrebnaElektricnaEnergija['ogrevanje']
                );

                $vracljiveIzgube = array_sum_values($vracljiveIzgube, $generator->vracljiveIzgube['ogrevanje'] ?? []);
                $vracljiveIzgube =
                    array_sum_values($vracljiveIzgube, $generator->vracljiveIzgubeAux['ogrevanje'] ?? []);

                $this->obnovljivaEnergija =
                    array_sum_values($this->obnovljivaEnergija, $generator->obnovljivaEnergija['ogrevanje']);
            }
        }

        $this->potrebnaEnergija = array_map(
            fn($e) => $e / $sistem->izkoristek,
            $this->potrebnaEnergija
        );

        $this->potrebnaEnergija = array_map(
            fn($e) => $e / $sistem->energent->maksimalniIzkoristek(),
            $this->potrebnaEnergija
        );

        // dejanska energija za ogrevanje
        $dejanskaEnergija = array_subtract_values($this->potrebnaEnergija, $this->obnovljivaEnergija);
        $this->energijaPoEnergentih[$sistem->energent->value] = array_sum($dejanskaEnergija);

        $elektricnaEnergija = array_sum($this->potrebnaElektricnaEnergija);

        if (count($this->potrebnaElektricnaEnergija) > 0 && $elektricnaEnergija != 0) {
            //$this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] = $elektricnaEnergija;
            $this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] =
                ($this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] ?? 0) +
                $elektricnaEnergija;
        }

        $obnovljivaEnergija = array_sum($this->obnovljivaEnergija);
        if (count($this->obnovljivaEnergija) > 0 && $obnovljivaEnergija != 0) {
            //$this->energijaPoEnergentih[TSSVrstaEnergenta::Okolje->value] = $obnovljivaEnergija;
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
