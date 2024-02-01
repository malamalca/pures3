<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\PrezracevalniSistemi;

use App\Calc\GF\TSS\PrezracevalniSistemi\Izbire\VrstaKrmiljenja;
use App\Calc\GF\TSS\TSSVrstaEnergenta;
use App\Lib\Calc;

class LokalniPrezracevalniSistem extends PrezracevalniSistem
{
    public VrstaKrmiljenja $krmiljenje;
    public float $mocVentilatorja;

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        parent::parseConfig($config);

        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->krmiljenje = VrstaKrmiljenja::from($config->krmiljenje ?? 'rocniVklop');
        $this->mocVentilatorja = $config->mocVentilatorja;
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
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = $stDni * 24;

            $this->potrebnaElektricnaEnergija[$mesec] = 0;
            $this->potrebnaEnergija[$mesec] = $stUr *
                $this->mocVentilatorja * $this->krmiljenje->faktorSistemaKrmiljenja() * $this->stevilo;

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
        $ret = parent::export();

        $ret->vrsta = $this->vrsta;

        $ret->faktorKrmiljenja = $this->krmiljenje->faktorSistemaKrmiljenja();
        $ret->mocVentilatorja = $this->mocVentilatorja;

        $ret->potrebnaEnergija = $this->potrebnaEnergija;
        $ret->potrebnaElektricnaEnergija = $this->potrebnaElektricnaEnergija;
        $ret->skupnaPotrebnaEnergija = $this->skupnaPotrebnaEnergija;
        $ret->energijaPoEnergentih = $this->energijaPoEnergentih;

        return $ret;
    }
}
