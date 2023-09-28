<?php
declare(strict_types=1);

namespace App\Calc\TSS\PrezracevalniSistemi;

use App\Calc\TSS\PrezracevalniSistemi\Izbire\VrstaKrmiljenja;
use App\Calc\TSS\PrezracevalniSistemi\Izbire\VrstaTransportaZraka;
use App\Calc\TSS\PrezracevalniSistemi\Podsistemi\TransportZraka;
use App\Calc\TSS\TSSVrstaEnergenta;
use App\Lib\Calc;

class CentralniPrezracevalniSistem extends PrezracevalniSistem
{
    public VrstaKrmiljenja $krmiljenje;

    public float $mocSenzorjev;
    public bool $razredH1H2;
    public float $volumenProjekt;

    public TransportZraka $odvod;
    public TransportZraka $dovod;

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

        $this->mocSenzorjev = $config->mocSenzorjev ?? 0;
        $this->razredH1H2 = $config->razredH1H2 ?? true;
        $this->krmiljenje = VrstaKrmiljenja::from($config->krmiljenje ?? 'rocniVklop');
        $this->volumenProjekt = $config->volumenProjekt;

        $this->odvod = new TransportZraka(
            VrstaTransportaZraka::Odvod,
            $this->volumenProjekt,
            $this->razredH1H2,
            $config->odvod ?? []
        );
        $this->dovod = new TransportZraka(
            VrstaTransportaZraka::Dovod,
            $this->volumenProjekt,
            $this->razredH1H2,
            $config->dovod ?? []
        );
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
        if (empty($this->odvod->volumen)) {
            $this->skupnaPotrebnaEnergija = 0;
        }
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = $stDni * 24;

            $this->potrebnaEnergija[$mesec] = $stUr *
                (($this->dovod->mocVentilatorja + $this->odvod->mocVentilatorja) *
                $this->krmiljenje->faktorSistemaKrmiljenja() + $this->mocSenzorjev / 1000) * $this->stevilo;

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
        $ret->razredH1H2 = $this->razredH1H2;

        $ret->vrsta = $this->vrsta;

        $ret->faktorKrmiljenja = $this->krmiljenje->faktorSistemaKrmiljenja();

        $ret->odvod = $this->odvod->export();
        $ret->dovod = $this->dovod->export();

        $ret->potrebnaEnergija = $this->potrebnaEnergija;
        $ret->skupnaPotrebnaEnergija = $this->skupnaPotrebnaEnergija;
        $ret->energijaPoEnergentih = $this->energijaPoEnergentih;

        return $ret;
    }
}
