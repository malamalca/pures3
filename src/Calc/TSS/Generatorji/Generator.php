<?php
declare(strict_types=1);

namespace App\Calc\TSS\Generatorji;

abstract class Generator
{
    public string $id;

    public float $nazivnaMoc;

    public $potrebnaEnergija;

    /**
     * Class Constructor
     *
     * @param string|\StdClass $config Configuration
     * @return void
     */
    public function __construct($config = null)
    {
        if ($config) {
            $this->parseConfig($config);
        }
    }

    /**
     * Loads configuration from json|StdClass
     *
     * @param string|\StdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->id = $config->id;
        $this->nazivnaMoc = $config->nazivnaMoc;
    }

    /**
     * Izračun toplotnih izgub generatorja
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \StdClass $cona Podatki cone
     * @param \StdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    abstract public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = []);
}
