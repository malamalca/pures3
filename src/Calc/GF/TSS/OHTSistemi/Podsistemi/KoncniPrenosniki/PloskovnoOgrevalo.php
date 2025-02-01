<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Izbire\VrstaHidravlicnegaUravnotezenja;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Izbire\VrstaIzolacijePloskovnihOgreval;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Izbire\VrstaSistemaPloskovnihOgreval;
use App\Lib\Calc;

class PloskovnoOgrevalo extends KoncniPrenosnik
{
    public const DELTAT_VRSTE_SISTEMOV = [0, 0, 0, 0.4, 0.7];
    public const DELTAT_SPECIFICNIH_IZGUB = [1.4, 0.5, 0.1];

    public string $vrsta = 'Ploskovna ogrevala';

    public float $deltaT_im = 0.0;
    public float $deltaT_sol = 0.0;

    public float $exponentOgrevala = 1.1;
    public float $deltaP_FBH = 25;

    protected VrstaSistemaPloskovnihOgreval $sistemOgreval;
    protected VrstaIzolacijePloskovnihOgreval $izolacija;
    protected VrstaHidravlicnegaUravnotezenja $hidravlicnoUravnotezenje;

    /**
     * Class Constructor
     *
     * @param \stdClass|null $config Configuration
     * @return void
     */
    public function __construct(\stdClass $config = null)
    {
        parent::__construct($config);

        $this->sistemOgreval = VrstaSistemaPloskovnihOgreval::from($config->sistem);
        $this->izolacija = VrstaIzolacijePloskovnihOgreval::from($config->izolacija);

        $this->hidravlicnoUravnotezenje =
            VrstaHidravlicnegaUravnotezenja::from($config->hidravlicnoUravnotezenje ?? 'neuravnotezeno');
        
        // Δθhydr - deltaTemp za hidravlično uravnoteženje sistema; prvi stolpec za stOgreval <= 10, drugi za > 10
        $this->deltaT_hydr = $this->hidravlicnoUravnotezenje->deltaTHydr($this);

        // Δθemb - deltaTemp za izolacijo (polje R206)
        $this->deltaT_emb = (self::DELTAT_VRSTE_SISTEMOV[$this->sistemOgreval->getOrdinal()] +
            self::DELTAT_SPECIFICNIH_IZGUB[$this->izolacija->getOrdinal()]) / 2;

        // Δθstr - deltaTemp Str (polje Q208)
        $this->deltaT_str = self::DELTAT_VRSTE_SISTEMOV[$this->sistemOgreval->getOrdinal()];
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
