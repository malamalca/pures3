<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\Razsvetljava;

use App\Calc\GF\Cone\Cona;
use App\Calc\GF\Cone\KlasifikacijeCone\KlasifikacijaConeFactory;
use App\Calc\GF\TSS\TSSSistem;
use App\Calc\GF\TSS\TSSVrstaEnergenta;
use App\Lib\Calc;
use stdClass;

class Razsvetljava extends TSSSistem
{
    public ?string $idCone;
    public string $tss = 'razsvetljava';

    public float $faktorDnevneSvetlobe;
    public ?float $faktorOblike = null;

    public ?float $mocSvetilk = null;

    public ?float $faktorZmanjsanjaSvetlobnegaToka;
    public ?float $faktorPrisotnosti;
    public ?float $ucinkovitostViraSvetlobe;
    public ?float $osvetlitevDelovnePovrsine;
    public ?float $faktorZmanjsaneOsvetlitveDelovnePovrsine;
    public ?float $faktorVzdrzevanja;
    public ?float $faktorNaravneOsvetlitve;
    public ?float $letnoUrPodnevi;
    public ?float $letnoUrPonoci;
    public ?float $varnostnaRazsvetljavaEnergijaZaPolnjenje;
    public ?float $varnostnaRazsvetljavaEnergijaZaDelovanje;

    public float $skupnaPotrebnaEnergija = 0;
    public array $potrebnaEnergija = [];
    public array $potrebnaElektricnaEnergija = [];
    public array $energijaPoEnergentih = [];

    /**
     * Class Constructor
     *
     * @param string|\stdClass $config Configuration
     * @param bool $referencnaStavba Določa ali gre za referenčno stavbo ali ne
     * @return void
     */
    public function __construct($config = null, bool $referencnaStavba = false)
    {
        $this->referencnaStavba = $referencnaStavba;
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
        $this->idCone = $config->idCone ?? null;

        $this->faktorDnevneSvetlobe = $config->faktorDnevneSvetlobe;

        $this->mocSvetilk = $config->mocSvetilk ?? null;

        // fluo     1-brez zatemnjevanja        0.9-z zatemnjevanjem
        // LED      1-brez zatemnjevanja        0.85-z zatemnjevanjem
        $this->faktorZmanjsanjaSvetlobnegaToka = $config->faktorZmanjsanjaSvetlobnegaToka ?? null;

        // stanovanjske     0.7-ročni vklop     0.55-avtomatsko zatemnjevanje       0.5-ročni vklop, samodejni izklop
        // pisarne          0.9-ročni vklop     0.85-avtomatsko zatemnjevanje       0.7-ročni vklop, samodejni izklop
        // ostale stavbe    1-za vse načine krmiljenja
        $this->faktorPrisotnosti = $config->faktorPrisotnosti ?? null;

                // halogen      30 lm/W
                // fluo         80 lm/W
                // LED          100-140 lm/W
                // ref.stavba   65 lm/W oz. 95 lm/W po letu 2025
                $this->ucinkovitostViraSvetlobe = $config->ucinkovitostViraSvetlobe ?? null;

                // stanovanjske 300 lx
                // poslovne     500 lx
                $this->osvetlitevDelovnePovrsine = $config->osvetlitevDelovnePovrsine ?? null;

                // F_CA = TSG stran 96
                $this->faktorZmanjsaneOsvetlitveDelovnePovrsine =
                    $config->faktorZmanjsaneOsvetlitveDelovnePovrsine ?? null;

                // CFL fluo     1.15
                // T5 fluo      1.1
                // LED          1
                $this->faktorVzdrzevanja = $config->faktorVzdrzevanja ?? null;

        $this->faktorNaravneOsvetlitve = $config->faktorNaravneOsvetlitve ?? null;

        $this->letnoUrPodnevi = $config->letnoUrPodnevi ?? null;
        $this->letnoUrPonoci = $config->letnoUrPonoci ?? null;

        $this->varnostnaRazsvetljavaEnergijaZaPolnjenje = $config->varnostna->energijaZaPolnjenje ?? null;
        $this->varnostnaRazsvetljavaEnergijaZaDelovanje = $config->varnostna->energijaZaDelovanje ?? null;
    }

    /**
     * Vrne privzeto vrednost za podano ime parametra
     *
     * @param string $name Ime parametra
     * @param \App\Calc\GF\Cone\Cona|\stdClass $cona Podatki cone
     * @return float
     */
    private function param(string $name, Cona|stdClass $cona): float
    {
        switch ($name) {
            case 'faktorZmanjsanjaSvetlobnegaToka':
                return $this->faktorZmanjsanjaSvetlobnegaToka ?? 1;
            case 'faktorPrisotnosti':
                return $this->faktorPrisotnosti ?? 0.7;
            case 'ucinkovitostViraSvetlobe':
                return $this->ucinkovitostViraSvetlobe ?? 95;
            case 'osvetlitevDelovnePovrsine':
                return $this->osvetlitevDelovnePovrsine ?? 300;
            case 'faktorZmanjsaneOsvetlitveDelovnePovrsine':
                return $this->faktorZmanjsaneOsvetlitveDelovnePovrsine ?? 1;
            case 'faktorVzdrzevanja':
                return $this->faktorVzdrzevanja ?? 1;
            case 'faktorNaravneOsvetlitve':
                return $this->faktorNaravneOsvetlitve ?? 0.6;
            case 'letnoUrPodnevi':
                return $this->letnoUrPodnevi ?? 1820;
            case 'letnoUrPonoci':
                return $this->letnoUrPonoci ?? 1680;
            default:
                return 0;
        }
    }

    /**
     * Analiza podsistema
     *
     * @param array $potrebnaEnergija Potrebna energija predhodnih TSS
     * @param \App\Calc\GF\Cone\Cona $cona Podatki cone
     * @param \stdClass|null $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function analiza($potrebnaEnergija, $cona, $okolje, $params = [])
    {
        if (is_null($this->faktorOblike)) {
            $faktorOblike = $cona->faktorOblikeCone ?? 1;
            if (!empty($params['referencnaStavba'])) {
                $faktorOblike = 1.4;
            }
        } else {
            $faktorOblike = $this->faktorOblike;
        }

        // ker je LAHKO odvisna od $cone oz. faktorjaOblikeCone
        if (is_null($this->mocSvetilk)) {
            $this->mocSvetilk = 1 / $this->param('ucinkovitostViraSvetlobe', $cona) *
                $this->param('osvetlitevDelovnePovrsine', $cona) *
                $faktorOblike *
                $this->param('faktorZmanjsaneOsvetlitveDelovnePovrsine', $cona) *
                $this->param('faktorVzdrzevanja', $cona);
        }

        if (is_null($this->letnoUrPodnevi)) {
            $klasifikacijaCone = KlasifikacijaConeFactory::create($cona->klasifikacija);
            $stUr = $klasifikacijaCone->letnoSteviloUrDelovanjaRazsvetljave();
            $this->letnoUrPodnevi = $stUr['podnevi'];
        }
        if (is_null($this->letnoUrPonoci)) {
            $klasifikacijaCone = KlasifikacijaConeFactory::create($cona->klasifikacija);
            $stUr = $klasifikacijaCone->letnoSteviloUrDelovanjaRazsvetljave();
            $this->letnoUrPonoci = $stUr['ponoci'];
        }

        $letnaDovedenaEnergija = ($this->param('faktorZmanjsanjaSvetlobnegaToka', $cona) *
            $this->param('faktorPrisotnosti', $cona) *
            $this->mocSvetilk / 1000 *
            (($this->param('letnoUrPodnevi', $cona) * $this->param('faktorNaravneOsvetlitve', $cona)) +
            $this->param('letnoUrPonoci', $cona)) +
            $this->param('varnostnaRazsvetljavaEnergijaZaPolnjenje', $cona) +
            $this->param('varnostnaRazsvetljavaEnergijaZaDelovanje', $cona)) * $cona->ogrevanaPovrsina;

        $mesecniUtezniFaktor = [1.25, 1.1, 0.94, 0.86, 0.83, 0.73, 0.79, 0.87, 0.94, 1.09, 1.21, 1.35];

        $this->skupnaPotrebnaEnergija = 0;
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = $stDni * 24;

            $this->potrebnaElektricnaEnergija[$mesec] = 0;
            $this->potrebnaEnergija[$mesec] = $letnaDovedenaEnergija * $stDni / 365 * $mesecniUtezniFaktor[$mesec];

            $this->skupnaPotrebnaEnergija += $this->potrebnaEnergija[$mesec];

            $this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] =
                ($this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] ?? 0) +
                $this->potrebnaEnergija[$mesec];
        }
    }

    /**
     * Izvoz kalkulacije
     *
     * @return \stdClass
     */
    public function export()
    {
        $ret = new \stdClass();

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if ($prop->isInitialized($this) && $prop->getValue($this) !== null) {
                $ret->{$prop->getName()} = $prop->getValue($this);
            }
        }

        return $ret;
    }
}
