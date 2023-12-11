<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji;

use App\Calc\GF\TSS\TSSInterface;

abstract class Generator extends TSSInterface
{
    public float $nazivnaMoc;

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
        $this->nazivnaMoc = $config->nazivnaMoc;
    }

    /**
     * Analiza podsistema
     *
     * @param array $potrebnaEnergija Potrebna energija predhodnih TSS
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function analiza($potrebnaEnergija, $sistem, $cona, $okolje, $params = [])
    {
        $this->potrebnaEnergija = [];
        $this->potrebnaElektricnaEnergija = [];
        $this->obnovljivaEnergija = [];
        $this->vracljiveIzgube = [];
        $this->vracljiveIzgubeAux = [];

        $this->potrebnaEnergija($potrebnaEnergija, $sistem, $cona, $okolje, $params);
        $this->potrebnaElektricnaEnergija($potrebnaEnergija, $sistem, $cona, $okolje, $params);
        $this->obnovljivaEnergija($potrebnaEnergija, $sistem, $cona, $okolje, $params);
    }

    /**
     * Izračun potrebne energije
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    abstract public function potrebnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = []);

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    abstract public function potrebnaElektricnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = []);

    /**
     * Uporabljena obnovljiva energija iz okolja
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    abstract public function obnovljivaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = []);

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $sistem = new \stdClass();
        $sistem->id = $this->id;

        $sistem->potrebnaEnergija = $this->potrebnaEnergija;
        $sistem->potrebnaElektricnaEnergija = $this->potrebnaElektricnaEnergija;
        $sistem->vneseneIzgube = $this->vneseneIzgube;

        return $sistem;
    }
}
