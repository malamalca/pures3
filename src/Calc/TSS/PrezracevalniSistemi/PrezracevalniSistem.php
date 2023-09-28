<?php
declare(strict_types=1);

namespace App\Calc\TSS\PrezracevalniSistemi;

abstract class PrezracevalniSistem
{
    public string $id;
    public string $idCone;
    public string $vrsta;

    public int $stevilo;

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
    abstract public function export();
}
