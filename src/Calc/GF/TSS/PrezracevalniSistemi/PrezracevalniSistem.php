<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\PrezracevalniSistemi;

use App\Calc\GF\TSS\TSSSistem;

abstract class PrezracevalniSistem extends TSSSistem
{
    public ?string $id;
    public string $idCone;
    public string $tss = 'prezracevanje';
    public string $vrsta;

    public int $stevilo;

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

        $this->id = $config->id;
        $this->idCone = $config->idCone;
        $this->vrsta = $config->vrsta;
        $this->stevilo = $config->stevilo ?? 1;
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
    abstract public function analiza($potrebnaEnergija, $cona, $okolje, $params = []);

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
        $ret->tss = $this->tss;

        $ret->porociloPodatki = $this->porociloPodatki;
        $ret->porociloNizi = $this->porociloNizi;

        return $ret;
    }
}
