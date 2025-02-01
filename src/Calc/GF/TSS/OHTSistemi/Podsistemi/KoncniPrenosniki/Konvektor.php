<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Izbire\VrstaHidravlicnegaUravnotezenja;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Izbire\VrstaNamestitve;
use App\Lib\Calc;

class Konvektor extends KoncniPrenosnik
{
    public float $deltaT_emb = 0.0;
    public float $deltaT_sol = 0.0;
    public float $deltaT_im = 0.0;
    public float $deltaT_str = 0.0;

    public float $exponentOgrevala = 1.1;

    protected VrstaNamestitve $namestitev;
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

        $this->namestitev = VrstaNamestitve::from($config->namestitev);

        $this->hidravlicnoUravnotezenje =
            VrstaHidravlicnegaUravnotezenja::from($config->hidravlicnoUravnotezenje ?? 'neuravnotezeno');

        // Δθhydr - deltaTemp za hidravlično uravnoteženje sistema; prvi stolpec za stOgreval <= 10, drugi za > 10
        $this->deltaT_hydr = $this->hidravlicnoUravnotezenje->deltaTHydr($this);
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
