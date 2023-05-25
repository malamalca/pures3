<?php
declare(strict_types=1);

namespace App\Calc\TSS\KoncniPrenosniki;

use App\Lib\Calc;

class Konvektor extends KoncniPrenosnik
{
    public $exponentOgrevala = 1.1;

    /**
     * Loads configuration from json|StdClass
     *
     * @param \StdClass|string|null $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        parent::parseConfig($config);
    }

    /**
     * Izračun toplotnih izgub končnega prenosnika
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \StdClass $cona Podatki cone
     * @param \StdClass $okolje Podatki cone
     * @return array
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje)
    {
        // Δθhydr - deltaTemp za hidravlično uravnoteženje sistema; prvi stolpec za stOgreval <= 10, drugi za > 10
        $deltaT_hydr = parent::DELTAT_HIDRAVLICNEGA_URAVNOTEZENJA_DO_10[$this->hidravlicnoUravnotezenje->getOrdinal()];

        // Δθctr - deltaTemp za regulacijo temperature; prvi stolpec sevala, drugi stolpec toplovod, h<4m
        $deltaT_ctr = parent::DELTAT_REGULACIJE_TEMPERATURE[$this->regulacijaTemperature->getOrdinal()];

        // Δθemb - deltaTemp za izolacijo (polje R206)
        $deltaT_emb = 0;

        // Δθstr - deltaTemp Str (polje Q208)
        $deltaT_str = 0;

        $deltaT = $deltaT_hydr + $deltaT_ctr + $deltaT_emb + $deltaT_str;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $faktorDeltaT = $deltaT / ($cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec]);
            $this->toplotneIzgube[$mesec] = $vneseneIzgube[$mesec] * $faktorDeltaT;
        }

        return $this->toplotneIzgube;
    }
}
