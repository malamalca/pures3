<?php
declare(strict_types=1);

namespace App\Calc\TSS\Razsvetljava;

use App\Calc\TSS\TSSVrstaEnergenta;
use App\Lib\Calc;

class Razsvetljava
{
    public string $id;
    public string $idCone;

    public float $faktorDnevneSvetlobe;

    public float $faktorZmanjsanjaSvetlobnegaToka = 0;
    public float $faktorPrisotnosti = 0;
    public float $ucinkovitostViraSvetlobe = 0;
    public float $osvetlitevDelovnePovrsine = 0;
    public float $faktorZmanjsaneOsvetlitveDelovnePovrsine = 0;
    public float $faktorVzdrzevanja = 0;
    public float $faktorNaravneOsvetlitve = 0;
    public float $letnoUrPodnevi = 0;
    public float $letnoUrPonoci = 0;

    public float $varnostnaRazsvetljavaEnergijaZaPolnjenje = 0;
    public float $varnostnaRazsvetljavaEnergijaZaDelovanje = 0;

    public float $mocSvetilk;

    public float $skupnaPotrebnaEnergija = 0;
    public array $potrebnaEnergija = [];
    public array $energijaPoEnergentih = [];

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

        $this->id = $config->id;
        $this->idCone = $config->idCone;

        $this->faktorDnevneSvetlobe = $config->faktorDnevneSvetlobe;

        // fluo     1-brez zatemnjevanja        0.9-z zatemnjevanjem
        // LED      1-brez zatemnjevanja        0.85-z zatemnjevanjem
        $this->faktorZmanjsanjaSvetlobnegaToka =
            $config->faktorZmanjsanjaSvetlobnegaToka ?? 1;

        // stanovanjske     0.7-ročni vklop     0.55-avtomatsko zatemnjevanje       0.5-ročni vklop, samodejni izklop
        // pisarne          0.9-ročni vklop     0.85-avtomatsko zatemnjevanje       0.7-ročni vklop, samodejni izklop
        // ostale stavbe    1-za vse načine krmiljenja
        $this->faktorPrisotnosti = $config->faktorPrisotnosti ?? 0.7;

                // halogen      30 lm/W
                // fluo         80 lm/W
                // LED          100-140 lm/W
                // ref.stavba   65 lm/W oz. 95 lm/W po letu 2025
                $this->ucinkovitostViraSvetlobe = $config->ucinkovitostViraSvetlobe ?? 65;

                // stanovanjske 300 lx
                // poslovne     500 lx
                $this->osvetlitevDelovnePovrsine = $config->osvetlitevDelovnePovrsine ?? 300;

                // F_CA = TSG stran 96
                $this->faktorZmanjsaneOsvetlitveDelovnePovrsine =
                    $config->faktorZmanjsaneOsvetlitveDelovnePovrsine ?? 1;

                // CFL fluo     1.15
                // T5 fluo      1.1
                // LED          1
                $this->faktorVzdrzevanja = $config->faktorVzdrzevanja ?? 1;

        $this->mocSvetilk = $config->mocSvetilk ?? null;

        $this->faktorNaravneOsvetlitve = $config->faktorNaravneOsvetlitve ?? 0.6;

        $this->letnoUrPodnevi = $config->letnoUrPodnevi ?? 1820;
        $this->letnoUrPonoci = $config->letnoUrPonoci ?? 1680;

        $this->varnostnaRazsvetljavaEnergijaZaPolnjenje = $config->varnostna->energijaZaPolnjenje ?? 0;
        $this->varnostnaRazsvetljavaEnergijaZaDelovanje = $config->varnostna->energijaZaDelovanje ?? 0;
    }

    /**
     * Analiza podsistema
     *
     * @param array $potrebnaEnergija Potrebna energija predhodnih TSS
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function analiza($potrebnaEnergija, $cona, $okolje, $params = [])
    {
        // ker je LAHKO odvisna od $cone oz. faktorjaOblikeCone
        $mocSvetilk = $this->mocSvetilk ??
            $this->ucinkovitostViraSvetlobe * $this->osvetlitevDelovnePovrsine * ($cona->faktorOblikeCone ?? 1) *
            $this->faktorZmanjsaneOsvetlitveDelovnePovrsine * $this->faktorVzdrzevanja;

        $letnaDovedenaEnergija = ($this->faktorZmanjsanjaSvetlobnegaToka *
            $this->faktorPrisotnosti *
            $mocSvetilk / 1000 *
            (($this->letnoUrPodnevi * $this->faktorNaravneOsvetlitve) +
            $this->letnoUrPonoci) +
            $this->varnostnaRazsvetljavaEnergijaZaPolnjenje +
            $this->varnostnaRazsvetljavaEnergijaZaDelovanje) * $cona->ogrevanaPovrsina;

        $mesecniUtezniFaktor = [1.25, 1.1, 0.94, 0.86, 0.83, 0.73, 0.79, 0.87, 0.94, 1.09, 1.21, 1.35];

        $this->skupnaPotrebnaEnergija = 0;
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = $stDni * 24;

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
        $ret->id = $this->id;
        $ret->idCone = $this->idCone;

        $ret->faktorDnevneSvetlobe = $this->faktorDnevneSvetlobe;

        //public float $faktorZmanjsanjaSvetlobnegaToka = 0;
        //public float $faktorPrisotnosti = 0;
        //public float $ucinkovitostViraSvetlobe = 0;
        //public float $osvetlitevDelovnePovrsine = 0;
        //public float $faktorZmanjsaneOsvetlitveDelovnePovrsine = 0;
        //public float $faktorVzdrzevanja = 0;
        //public float $faktorNaravneOsvetlitve = 0;
        $ret->letnoUrPodnevi = $this->letnoUrPodnevi;
        $ret->letnoUrPonoci = $this->letnoUrPonoci;
        //public float $letnoUrPonoci = 0;

        //public float $varnostnaRazsvetljavaEnergijaZaPolnjenje = 0;
        //public float $varnostnaRazsvetljavaEnergijaZaDelovanje = 0;

        $ret->mocSvetilk = $this->mocSvetilk;

        $ret->potrebnaEnergija = $this->potrebnaEnergija;
        $ret->skupnaPotrebnaEnergija = $this->skupnaPotrebnaEnergija;
        $ret->energijaPoEnergentih = $this->energijaPoEnergentih;

        return $ret;
    }
}
