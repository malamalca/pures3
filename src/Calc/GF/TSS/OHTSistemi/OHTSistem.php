<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\GeneratorFactory;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\HranilnikFactory;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosnikFactory;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\RazvodFactory;
use App\Calc\GF\TSS\OHTSistemi\Sistemi\Hlajenje;
use App\Calc\GF\TSS\OHTSistemi\Sistemi\Ogrevanje;
use App\Calc\GF\TSS\OHTSistemi\Sistemi\TSV;
use App\Calc\GF\TSS\TSSVrstaEnergenta;

abstract class OHTSistem
{
    public ?string $id;
    public ?string $idCone;
    public string $tss = 'oht';

    public string $vrsta;
    public float $izkoristek = 1;
    public bool $jeOgrevalniSistem = true;
    public TSSVrstaEnergenta $energent;

    /**
     * Povprecna obremenitev podsistemov
     */
    public array $povprecnaObremenitev;

    public array $podsistemi = [];

    public array $koncniPrenosniki = [];
    public array $razvodi = [];
    public array $hranilniki = [];
    public array $generatorji = [];

    public array $potrebnaEnergija = [];
    public array $potrebnaElektricnaEnergija = [];
    public array $potrebnaEnergijaHlajenje = [];

    public array $obnovljivaEnergija = [];
    public array $vracljiveIzgube = [];
    public array $energijaPoEnergentih = [];

    public ?Ogrevanje $ogrevanje;
    public ?TSV $tsv;
    public ?Hlajenje $hlajenje;

    // array namenjen da se vnaša vračljiva energija iz drugih sistemov
    public array $vracljiveIzgubeVOgrevanje = [];

    public float $letnaUcinkovitostOgrHlaTsv = 0;
    public float $minLetnaUcinkovitostOgrHlaTsv = 0;

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
    protected function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->id = $config->id ?? null;
        $this->idCone = $config->idCone ?? null;
        $this->vrsta = $config->vrsta;
        $this->energent = TSSVrstaEnergenta::from($config->energent ?? 'elektrika');

        // nameni TSS
        if (!empty($config->ogrevanje)) {
            $this->ogrevanje = new Ogrevanje($config->ogrevanje);
        }
        if (!empty($config->tsv)) {
            $this->tsv = new TSV($config->tsv);
        }
        if (!empty($config->hlajenje)) {
            $this->hlajenje = new Hlajenje($config->hlajenje);
        }

        if (!empty($config->razvodi)) {
            foreach ($config->razvodi as $razvod) {
                $this->razvodi[] = RazvodFactory::create($razvod->vrsta, $razvod);
            }
        }

        if (!empty($config->prenosniki)) {
            foreach ($config->prenosniki as $prenosnik) {
                $this->koncniPrenosniki[] = KoncniPrenosnikFactory::create($prenosnik->vrsta, $prenosnik);
            }
        }

        if (!empty($config->hranilniki)) {
            foreach ($config->hranilniki as $hranilnik) {
                $this->hranilniki[] = HranilnikFactory::create($hranilnik->vrsta, $hranilnik);
            }
        }

        if (!empty($config->generatorji)) {
            foreach ($config->generatorji as $generator) {
                $this->generatorji[] = GeneratorFactory::create($generator->vrsta, $generator);
            }
        }
    }

    /**
     * Funkcija mora vrniti standardno moč
     * QN – standardna potrebna toplotna moč za ogrevanje ali hlajenje (cone) – skladno s SIST
     * EN 12831 ali z drugimi enakovrednimi, v stroki priznanimi računskimi metodami [kW]
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return float
     */
    abstract public function standardnaMoc($cona, $okolje): float;

    /**
     * Funkcija mora vrniti število ur delovanja
     *
     * @param int $mesec Mesec
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return float
     */
    abstract public function steviloUrDelovanja($mesec, $cona, $okolje): float;

    /**
     * Glavna metoda za analizo ogrevalnega sistema
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public function analiza($cona, $okolje)
    {
        $this->energijaPoEnergentih = [];

        $this->potrebnaEnergija = [];
        $this->potrebnaElektricnaEnergija = [];
        $this->obnovljivaEnergija = [];

        $this->podsistemi = [];

        $skupnaDovedenaEnergijaOgrHlaTsv = 0;
        $utezenaDovedenaEnergijaOgrHlaTsv = 0;

        // najprej analiziram toplo vodo
        if (!empty($this->tsv)) {
            $skupnaDovedenaEnergijaOgrHlaTsv += $cona->skupnaEnergijaTSV;

            $this->tsv->analiza([], $this, $cona, $okolje);
            $this->vracljiveIzgubeVOgrevanje = $this->tsv->vracljiveIzgubeVOgrevanje;

            $this->potrebnaEnergija =
                array_sum_values($this->potrebnaEnergija, $this->tsv->potrebnaEnergija);

            $this->potrebnaElektricnaEnergija =
                array_sum_values($this->potrebnaElektricnaEnergija, $this->tsv->potrebnaElektricnaEnergija);

            $this->obnovljivaEnergija =
                array_sum_values($this->obnovljivaEnergija, $this->tsv->obnovljivaEnergija);

            $dejanskaEnergija =
                array_subtract_values($this->tsv->potrebnaEnergija, $this->tsv->obnovljivaEnergija);

            foreach ($this->tsv->energijaPoEnergentih as $energentId => $energija) {
                if ($energija != 0) {
                    $this->energijaPoEnergentih[$energentId] =
                        ($this->energijaPoEnergentih[$energentId] ?? 0) + $energija;
                }
            }

            $utezenaDovedenaEnergijaOgrHlaTsv +=
                array_sum($dejanskaEnergija) * $this->energent->utezniFaktor('tot') +
                array_sum($this->tsv->potrebnaElektricnaEnergija) * TSSVrstaEnergenta::Elektrika->utezniFaktor('tot') +
                array_sum($this->tsv->obnovljivaEnergija) * TSSVrstaEnergenta::Okolje->utezniFaktor('tot');
        }

        // potem ogrevanje
        if (!empty($this->ogrevanje)) {
            $skupnaDovedenaEnergijaOgrHlaTsv += $cona->skupnaEnergijaOgrevanje;

            $this->ogrevanje->vrnjeneIzgubeVOgrevanje = $this->vracljiveIzgubeVOgrevanje;
            $this->ogrevanje->analiza([], $this, $cona, $okolje);

            $this->potrebnaEnergija =
                array_sum_values($this->potrebnaEnergija, $this->ogrevanje->potrebnaEnergija);

            $this->potrebnaElektricnaEnergija =
                array_sum_values($this->potrebnaElektricnaEnergija, $this->ogrevanje->potrebnaElektricnaEnergija);

            $this->obnovljivaEnergija =
                array_sum_values($this->obnovljivaEnergija, $this->ogrevanje->obnovljivaEnergija);

            $dejanskaEnergija =
                array_subtract_values($this->ogrevanje->potrebnaEnergija, $this->ogrevanje->obnovljivaEnergija);

            foreach ($this->ogrevanje->energijaPoEnergentih as $energentId => $energija) {
                if ($energija != 0) {
                    $this->energijaPoEnergentih[$energentId] =
                        ($this->energijaPoEnergentih[$energentId] ?? 0) + $energija;
                }
            }

            $utezenaDovedenaEnergijaOgrHlaTsv +=
                array_sum($dejanskaEnergija) * $this->energent->utezniFaktor('tot') +
                array_sum($this->ogrevanje->potrebnaElektricnaEnergija) *
                TSSVrstaEnergenta::Elektrika->utezniFaktor('tot') +
                array_sum($this->ogrevanje->obnovljivaEnergija) * TSSVrstaEnergenta::Okolje->utezniFaktor('tot');
        }

        // potem hlajenje
        if (!empty($this->hlajenje)) {
            $skupnaDovedenaEnergijaOgrHlaTsv += $cona->skupnaEnergijaHlajenje;

            //$this->hlajenje->vrnjeneIzgubeVOgrevanje = $this->vracljiveIzgubeVOgrevanje;
            $this->hlajenje->analiza([], $this, $cona, $okolje);

            $this->potrebnaEnergija =
                array_sum_values($this->potrebnaEnergija, $this->hlajenje->potrebnaEnergija);

            $this->potrebnaElektricnaEnergija =
                array_sum_values($this->potrebnaElektricnaEnergija, $this->hlajenje->potrebnaElektricnaEnergija);

            $this->obnovljivaEnergija =
                array_sum_values($this->obnovljivaEnergija, $this->hlajenje->obnovljivaEnergija);

            $dejanskaEnergija =
                array_subtract_values($this->hlajenje->potrebnaEnergija, $this->hlajenje->obnovljivaEnergija);

            foreach ($this->hlajenje->energijaPoEnergentih as $energentId => $energija) {
                if ($energija != 0) {
                    $this->energijaPoEnergentih[$energentId] =
                        ($this->energijaPoEnergentih[$energentId] ?? 0) + $energija;
                }
            }

            $utezenaDovedenaEnergijaOgrHlaTsv +=
                array_sum($dejanskaEnergija) * $this->energent->utezniFaktor('tot') +
                array_sum($this->hlajenje->potrebnaElektricnaEnergija) *
                TSSVrstaEnergenta::Elektrika->utezniFaktor('tot') +
                array_sum($this->hlajenje->obnovljivaEnergija) * TSSVrstaEnergenta::Okolje->utezniFaktor('tot');
        }

        if ($utezenaDovedenaEnergijaOgrHlaTsv > 0) {
            $this->letnaUcinkovitostOgrHlaTsv = $skupnaDovedenaEnergijaOgrHlaTsv / $utezenaDovedenaEnergijaOgrHlaTsv;
        } else {
            $this->letnaUcinkovitostOgrHlaTsv = 0;
        }
        $this->minLetnaUcinkovitostOgrHlaTsv = $this->energent->minimalniIzkoristekOgrHlaTsv();
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $sistem = new \stdClass();
        $sistem->id = $this->id;
        $sistem->idCone = $this->idCone;
        $sistem->tss = $this->tss;
        $sistem->vrsta = $this->vrsta;
        $sistem->energent = $this->energent;
        $sistem->jeOgrevalniSistem = $this->jeOgrevalniSistem;

        $sistem->potrebnaEnergija = $this->potrebnaEnergija;
        $sistem->potrebnaEnergijaHlajenje = $this->potrebnaEnergijaHlajenje;
        $sistem->potrebnaElektricnaEnergija = $this->potrebnaElektricnaEnergija;
        $sistem->obnovljivaEnergija = $this->obnovljivaEnergija;
        $sistem->vracljiveIzgube = $this->vracljiveIzgube;

        $sistem->energijaPoEnergentih = $this->energijaPoEnergentih;

        $sistem->letnaUcinkovitostOgrHlaTsv = $this->letnaUcinkovitostOgrHlaTsv;
        $sistem->minLetnaUcinkovitostOgrHlaTsv = $this->minLetnaUcinkovitostOgrHlaTsv;

        $sistem->porociloPodatki = [];
        $sistem->porociloNizi = [];

        if (!empty($this->ogrevanje)) {
            $sistem->ogrevanje = $this->ogrevanje->export();
            $sistem->porociloNizi = array_merge($sistem->porociloNizi, $sistem->ogrevanje->porociloNizi);
            $sistem->porociloPodatki = array_merge($sistem->porociloPodatki, $sistem->ogrevanje->porociloPodatki);
        }
        if (!empty($this->tsv)) {
            $sistem->tsv = $this->tsv->export();
            $sistem->porociloNizi = array_merge($sistem->porociloNizi, $sistem->tsv->porociloNizi);
            $sistem->porociloPodatki = array_merge($sistem->porociloPodatki, $sistem->tsv->porociloPodatki);
        }
        if (!empty($this->hlajenje)) {
            $sistem->hlajenje = $this->hlajenje->export();
            $sistem->porociloNizi = array_merge($sistem->porociloNizi, $sistem->hlajenje->porociloNizi);
            $sistem->porociloPodatki = array_merge($sistem->porociloPodatki, $sistem->hlajenje->porociloPodatki);
        }

        if (!empty($this->koncniPrenosniki)) {
            $sistem->prenosniki = [];
            foreach ($this->koncniPrenosniki as $prenosnik) {
                $sistem->prenosniki[] = $prenosnik->export();
            }
        }

        if (!empty($this->razvodi)) {
            $sistem->razvodi = [];
            foreach ($this->razvodi as $razvod) {
                $sistem->razvodi[] = $razvod->export();
            }
        }

        if (!empty($this->hranilniki)) {
            $sistem->hranilniki = [];
            foreach ($this->hranilniki as $hranilnik) {
                $sistem->hranilniki[] = $hranilnik->export();
            }
        }

        if (!empty($this->generatorji)) {
            $sistem->generatorji = [];
            foreach ($this->generatorji as $generator) {
                $sistem->generatorji[] = $generator->export();
            }
        }

        return $sistem;
    }
}
