<?php
declare(strict_types=1);

namespace App\Calc\TSS\KoncniPrenosniki;

use App\Calc\TSS\KoncniPrenosniki\Izbire\VrstaIzolacijePloskovnihOgreval;
use App\Calc\TSS\KoncniPrenosniki\Izbire\VrstaSistemaPloskovnihOgreval;
use App\Lib\Calc;

class PloskovnoOgrevalo extends KoncniPrenosnik
{
    public const DELTAT_VRSTE_SISTEMOV = [0, 0, 0, 0.4, 0.7];
    public const DELTAT_SPECIFICNIH_IZGUB = [1.4, 0.5, 0.1];

    public $exponentOgrevala = 1.1;
    public $deltaP_FBH = 25;

    protected VrstaSistemaPloskovnihOgreval $sistemOgreval;
    protected VrstaIzolacijePloskovnihOgreval $izolacija;

    /**
     * Loads configuration from json|stdClass
     *
     * @param \stdClass|string|null $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        parent::parseConfig($config);

        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->sistemOgreval = VrstaSistemaPloskovnihOgreval::from($config->sistem);
        $this->izolacija = VrstaIzolacijePloskovnihOgreval::from($config->izolacija);
    }

    /**
     * Izračun toplotnih izgub končnega prenosnika
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki cone
     * @return array
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje)
    {
        // Δθhydr - deltaTemp za hidravlično uravnoteženje sistema; prvi stolpec za stOgreval <= 10, drugi za > 10
        $deltaT_hydr = parent::DELTAT_HIDRAVLICNEGA_URAVNOTEZENJA_DO_10[$this->hidravlicnoUravnotezenje->getOrdinal()];

        // Δθctr - deltaTemp za regulacijo temperature; prvi stolpec sevala, drugi stolpec toplovod, h<4m
        $deltaT_ctr = parent::DELTAT_REGULACIJE_TEMPERATURE[$this->regulacijaTemperature->getOrdinal()];

        // Δθemb - deltaTemp za izolacijo (polje R206)
        $deltaT_emb = self::DELTAT_VRSTE_SISTEMOV[$this->sistemOgreval->getOrdinal()] +
            self::DELTAT_SPECIFICNIH_IZGUB[$this->izolacija->getOrdinal()];

        // Δθstr - deltaTemp Str (polje Q208)
        $deltaT_str = self::DELTAT_VRSTE_SISTEMOV[$this->sistemOgreval->getOrdinal()];

        $deltaT = $deltaT_hydr + $deltaT_ctr + $deltaT_emb + $deltaT_str;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $faktorDeltaT = $deltaT / ($cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec]);
            $this->toplotneIzgube[$mesec] = $vneseneIzgube[$mesec] * $faktorDeltaT;
        }

        return $this->toplotneIzgube;
    }
}
