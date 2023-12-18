<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi;

use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\GeneratorFactory;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\HranilnikFactory;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosnikFactory;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\RazvodFactory;
use App\Calc\GF\TSS\OgrevalniSistemi\Sistemi\Ogrevanje;
use App\Calc\GF\TSS\OgrevalniSistemi\Sistemi\TSV;
use App\Calc\GF\TSS\TSSVrstaEnergenta;
use App\Lib\Calc;

abstract class OHTSistem
{
    public ?string $id;
    public ?string $idCone;
    public string $vrsta;
    public float $izkoristek = 1;
    public bool $jeOgrevalniSistem = true;
    public TSSVrstaEnergenta $energent;

    /**
     * QN – standardna potrebna toplotna moč za ogrevanje (cone) – moč ogreval, skladno s SIST
     * EN 12831 ali z drugimi enakovrednimi, v stroki priznanimi računskimi metodami [kW]
     *
     * @var float $standardnaMoc
     */
    public float $standardnaMoc;

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
    public array $obnovljivaEnergija = [];
    public array $vracljiveIzgube = [];

    public ?Ogrevanje $ogrevanje;
    public ?TSV $tsv;
    public ?\stdClass $hlajenje;

    public array $energijaPoEnergentih = [];
    public array $energijaPoEnergentihOgrevanje = [];
    public array $energijaPoEnergentihTSV = [];
    public array $energijaPoEnergentihHlajenje = [];

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
            $this->hlajenje = $config->hlajenje;
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

        if ($this->energijaPoEnergentih[TSSVrstaEnergenta::Okolje->value] == 0) {
            unset($this->energijaPoEnergentih[TSSVrstaEnergenta::Okolje->value]);
        }
        if ($this->energijaPoEnergentihOgrevanje[TSSVrstaEnergenta::Okolje->value] == 0) {
            unset($this->energijaPoEnergentihOgrevanje[TSSVrstaEnergenta::Okolje->value]);
        }
        if ($this->energijaPoEnergentihTSV[TSSVrstaEnergenta::Okolje->value] == 0) {
            unset($this->energijaPoEnergentihTSV[TSSVrstaEnergenta::Okolje->value]);
        }
        /*if ($this->energijaPoEnergentihHlajenje[TSSVrstaEnergenta::Okolje->value] == 0) {
            unset($this->energijaPoEnergentihHlajenje[TSSVrstaEnergenta::Okolje->value]);
        }*/

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
        $sistem->vrsta = $this->vrsta;
        $sistem->energent = $this->energent;
        $sistem->jeOgrevalniSistem = $this->jeOgrevalniSistem;

        $sistem->potrebnaEnergija = $this->potrebnaEnergija;
        $sistem->potrebnaElektricnaEnergija = $this->potrebnaElektricnaEnergija;
        $sistem->obnovljivaEnergija = $this->obnovljivaEnergija;
        $sistem->vracljiveIzgube = $this->vracljiveIzgube;

        $sistem->energijaPoEnergentih = $this->energijaPoEnergentih;
        $sistem->energijaPoEnergentihOgrevanje = $this->energijaPoEnergentihOgrevanje;
        $sistem->energijaPoEnergentihTSV = $this->energijaPoEnergentihTSV;
        $sistem->energijaPoEnergentihHlajenje = $this->energijaPoEnergentihHlajenje;

        $sistem->letnaUcinkovitostOgrHlaTsv = $this->letnaUcinkovitostOgrHlaTsv;
        $sistem->minLetnaUcinkovitostOgrHlaTsv = $this->minLetnaUcinkovitostOgrHlaTsv;

        if (!empty($this->ogrevanje)) {
            $sistem->ogrevanje = $this->ogrevanje;
        }
        if (!empty($this->tsv)) {
            $sistem->tsv = $this->tsv;
        }
        if (!empty($this->hlajenje)) {
            $sistem->hlajenje = $this->hlajenje;
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
