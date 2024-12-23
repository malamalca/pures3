<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki;

use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\Izbire\VrstaHidravlicnegaUravnotezenja;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\Izbire\VrstaNamestitve;
use App\Lib\Calc;

class Radiator extends KoncniPrenosnik
{
    public const DELTAT_REZIM = [0.4, 0.5, 0.7];
    public const DELTAT_NAMESTITEV = [1.3, 0.3, 1.7, 1.2];

    public float $exponentOgrevala = 1.33;

    protected VrstaNamestitve $namestitev;
    protected VrstaHidravlicnegaUravnotezenja $hidravlicnoUravnotezenje;

    /**
     * Loads configuration from json|stdClass
     *
     * @param \stdClass|null $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        parent::parseConfig($config);

        $this->namestitev = VrstaNamestitve::from($config->namestitev);
        $this->hidravlicnoUravnotezenje =
            VrstaHidravlicnegaUravnotezenja::from($config->hidravlicnoUravnotezenje ?? 'neuravnotezeno');
    }

    /**
     * Izračun toplotnih izgub končnega prenosnika
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki cone
     * @param array $params Dodatni parametri
     * @return array
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $rezim = $params['rezim'];

        // Δθhydr - deltaTemp za hidravlično uravnoteženje sistema; prvi stolpec za stOgreval <= 10, drugi za > 10
        $deltaT_hydr = parent::DELTAT_HIDRAVLICNEGA_URAVNOTEZENJA_DO_10[$this->hidravlicnoUravnotezenje->getOrdinal()];

        // Δθctr - deltaTemp za regulacijo temperature; prvi stolpec sevala, drugi stolpec toplovod, h<4m
        $deltaT_ctr = parent::DELTAT_REGULACIJE_TEMPERATURE[$this->regulacijaTemperature->getOrdinal()];

        // Δθemb - deltaTemp za izolacijo (polje R206)
        $deltaT_emb = 0;

        // Δθstr - deltaTemp Str (polje Q208)
        $deltaT_str = self::DELTAT_NAMESTITEV[$this->namestitev->getOrdinal()] +
            self::DELTAT_REZIM[$rezim->getOrdinal()];

        $deltaT = $deltaT_hydr + $deltaT_ctr + $deltaT_emb + $deltaT_str;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            if ($cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec] != 0.0) {
                $faktorDeltaT = $deltaT / ($cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec]);
            } else {
                $faktorDeltaT = $deltaT;
            }
            $this->toplotneIzgube[$mesec] = $vneseneIzgube[$mesec] * $faktorDeltaT;
        }

        return $this->toplotneIzgube;
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $sistem = parent::export();
        $sistem->hidravlicnoUravnotezenje = $this->hidravlicnoUravnotezenje->toString();

        return $sistem;
    }
}
