<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Hranilniki;

use App\Calc\GF\TSS\TSSVrstaEnergenta;
use App\Lib\Calc;

class NeposrednoOgrevanHranilnik extends Hranilnik
{
    public bool $znotrajOvoja;

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
        parent::parseConfig($config);

        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->znotrajOvoja = !empty($config->znotrajOvoja);
    }

    /**
     * Analiza podsistema
     *
     * @param array $toplotneIzgube Toplotne izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function analiza($toplotneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $this->toplotneIzgube($toplotneIzgube, $sistem, $cona, $okolje, $params);
    }

    /**
     * Izračun toplotnih izgub
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki cone
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $temperaturaOkolice = $this->znotrajOvoja ? $cona->notranjaTOgrevanje : 13;

        // q w,s,l - dnevne toplotne izgube hranilnika v stanju obratovalne pripravljenosti [kWh]. Podatek
        // proizvajalca ali enačba 123a ali 123b.
        if ($sistem->energent == TSSVrstaEnergenta::Elektrika) {
            $dnevneIzgube = 0.29 + 0.019 * pow($this->volumen, 0.8);
        } else {
            $dnevneIzgube = 2 + 0.033 * pow($this->volumen, 1.1);
        }

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            $UA = $dnevneIzgube * (55 - $temperaturaOkolice) / 45 * 1000 / 24 / (60 - $temperaturaOkolice);

            $this->toplotneIzgube[$mesec] = $dnevneIzgube * (55 - $temperaturaOkolice) / 45 * $stDni;
        }

        $this->vracljiveIzgube = $this->toplotneIzgube;

        return $this->toplotneIzgube;
    }
}
