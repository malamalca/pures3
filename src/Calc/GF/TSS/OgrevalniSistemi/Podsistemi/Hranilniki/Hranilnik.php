<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Hranilniki;

abstract class Hranilnik
{
    public string $id;

    public float $volumen;

    public array $toplotneIzgube = [];
    public array $potrebnaElektricnaEnergija = [];
    public array $vracljiveIzgube = [];
    public array $vracljiveIzgubeAux = [];

    /**
     * Class Constructor
     *
     * @param \stdClass|string|null $config Configuration
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

        $this->volumen = $config->volumen ?? 0;
        $this->id = $config->id ?? null;
    }

    /**
     * Izračun toplotnih izgub
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki cone
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    abstract public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = []);

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $sistem = new \stdClass();
        $sistem->id = $this->id;
        $sistem->volumen = $this->volumen;

        $sistem->toplotneIzgube = $this->toplotneIzgube;

        return $sistem;
    }
}
