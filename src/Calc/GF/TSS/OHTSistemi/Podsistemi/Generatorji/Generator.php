<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji;

use App\Calc\GF\TSS\TSSInterface;

abstract class Generator extends TSSInterface
{
    public float $nazivnaMoc;
    public array $vneseneIzgube = [];
    public array $nepokritaEnergija = [];

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
        $this->nazivnaMoc = $config->nazivnaMoc ?? 0;
    }

    /**
     * Analiza podsistema
     *
     * @param array $toplotneIzgube Potrebna energija predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function analiza($toplotneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'] ?? 'ogrevanje';

        $this->toplotneIzgube[$namen] = [];
        $this->potrebnaElektricnaEnergija[$namen] = [];
        $this->obnovljivaEnergija[$namen] = [];
        $this->vracljiveIzgube[$namen] = [];
        $this->vracljiveIzgubeAux[$namen] = [];

        $this->toplotneIzgube($toplotneIzgube, $sistem, $cona, $okolje, $params);
        $this->potrebnaElektricnaEnergija($toplotneIzgube, $sistem, $cona, $okolje, $params);
        $this->obnovljivaEnergija($toplotneIzgube, $sistem, $cona, $okolje, $params);
    }

    /**
     * Izračun potrebne energije
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    abstract public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = []);

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
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
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
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
        $sistem = parent::export();
        $sistem->vneseneIzgube = $this->vneseneIzgube;

        return $sistem;
    }
}
