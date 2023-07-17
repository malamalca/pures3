<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki;

use App\Calc\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\Izbire\VrstaNamestitve;
use App\Calc\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\Izbire\VrstaRegulacijeTemperature;
use App\Lib\Calc;

class ElektricnoOgrevalo extends KoncniPrenosnik
{
    public string $vrsta = 'Električna ogrevala';

    protected VrstaNamestitve $namestitev;
    protected VrstaRegulacijeTemperature $regulacija;

    /**
     * Loads configuration from json|stdClass
     *
     * @param \stdClass|null $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        parent::parseConfig($config);

        //$this->namestitev = VrstaNamestitve::from($config->namestitev);
        $this->regulacija = VrstaRegulacijeTemperature::from($config->regulacija);
    }

    /**
     * Izračun toplotnih izgub končnega prenosnika
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $deltaT = $this->regulacija->deltaTElektricnoOgrevalo();
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $faktorDeltaT = $deltaT / ($cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec]);

            $this->toplotneIzgube[$mesec] = $vneseneIzgube[$mesec] * $faktorDeltaT;
        }

        return $this->toplotneIzgube;
    }
}
