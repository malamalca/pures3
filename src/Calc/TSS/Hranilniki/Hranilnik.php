<?php
declare(strict_types=1);

namespace App\Calc\TSS\Hranilniki;

abstract class Hranilnik
{
    public string $id;

    public $volumen;

    public $toplotneIzgube;
    public $potrebnaElektricnaEnergija;
    public $vracljiveIzgubeAux;

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
    }

    /**
     * Izračun toplotnih izgub
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki cone
     * @return array
     */
    abstract public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje);
}
