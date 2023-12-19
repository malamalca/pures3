<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\PrezracevalniSistemi\Podsistemi;

use App\Calc\GF\TSS\PrezracevalniSistemi\Izbire\VrstaFiltraZraka;
use App\Calc\GF\TSS\PrezracevalniSistemi\Izbire\VrstaTransportaZraka;

class TransportZraka
{
    public VrstaTransportaZraka $vrsta;
    public VrstaFiltraZraka $vrstaFiltra = VrstaFiltraZraka::BrezFiltra;
    public float $volumen;
    public float $mocVentilatorja = 0;
    public bool $visokIzkoristek;

    /**
     * Class Constructor
     *
     * @param \App\Calc\GF\TSS\PrezracevalniSistemi\Izbire\VrstaTransportaZraka $vrsta Vrsta transporta zraka
     * @param float $volumen Volumen
     * @param bool $visokIzkoristek Je sistem H1H2
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function __construct(VrstaTransportaZraka $vrsta, $volumen, $visokIzkoristek, $config = null)
    {
        $this->vrsta = $vrsta;
        $this->volumen = $volumen;
        $this->visokIzkoristek = $visokIzkoristek;

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

        $this->vrstaFiltra = VrstaFiltraZraka::from($config->filter ?? 'brez');
        $this->mocVentilatorja = $config->mocVentilatorja ?? $this->izracunMociVentilatorja();
    }

    /**
     * Izračun moči ventilatorja, kadar moč ni vpisana neposredno (ni znana)
     *
     * @return float
     */
    public function izracunMociVentilatorja()
    {
        $dodatekH1H2 = $this->visokIzkoristek ? 300 : 0;

        // kW/(m³/h)
        $spf = ($this->vrsta->faktorSpf() * 3600 + $this->vrstaFiltra->dodatekFiltra() + $dodatekH1H2) / 3600000;

        $mocVentilatorja = $spf * $this->volumen;

        return $mocVentilatorja;
    }

    /**
     * Izvoz kalkulacije
     *
     * @return \stdClass
     */
    public function export()
    {
        $ret = new \stdClass();
        $ret->volumen = $this->volumen;
        $ret->mocVentilatorja = $this->mocVentilatorja;
        $ret->filter = $this->vrstaFiltra->value;

        return $ret;
    }
}
