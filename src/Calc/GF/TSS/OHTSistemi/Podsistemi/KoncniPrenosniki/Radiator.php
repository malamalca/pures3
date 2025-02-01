<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Izbire\VrstaHidravlicnegaUravnotezenja;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Izbire\VrstaNamestitve;

class Radiator extends KoncniPrenosnik
{
    public const DELTAT_REZIM = [0.4, 0.5, 0.7];
    public const DELTAT_NAMESTITEV = [1.3, 0.3, 1.7, 1.2];

    public float $deltaT_emb = 0.0;
    public float $deltaT_sol = 0.0;
    public float $deltaT_im = 0.0;

    public float $exponentOgrevala = 1.33;

    protected VrstaNamestitve $namestitev;
    protected VrstaHidravlicnegaUravnotezenja $hidravlicnoUravnotezenje;

    /**
     * Class Constructor
     *
     * @param \stdClass|null $config Configuration
     * @return void
     */
    public function __construct(?\stdClass $config = null)
    {
        parent::__construct($config);

        $this->namestitev = VrstaNamestitve::from($config->namestitev);

        $this->hidravlicnoUravnotezenje =
            VrstaHidravlicnegaUravnotezenja::from($config->hidravlicnoUravnotezenje ?? 'neuravnotezeno');

        // Δθhydr - deltaTemp za hidravlično uravnoteženje sistema; prvi stolpec za stOgreval <= 10, drugi za > 10
        $this->deltaT_hydr = $this->hidravlicnoUravnotezenje->deltaTHydr($this);
    }

    /**
     * Izračun toplotnih izgub končnega prenosnika
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki cone
     * @param array $params Dodatni parametri
     * @return array
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        /** @var \App\Calc\GF\TSS\OHTSistemi\Izbire\VrstaRezima $rezim */
        $rezim = $sistem->ogrevanje->rezim;

        // Δθstr - deltaTemp Str (polje Q208)
        $this->deltaT_str = (self::DELTAT_NAMESTITEV[$this->namestitev->getOrdinal()] +
            self::DELTAT_REZIM[$rezim->getOrdinal()]) / 2;

        return parent::toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params);
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
        $sistem->namestitev = $this->namestitev->toString();

        return $sistem;
    }
}
