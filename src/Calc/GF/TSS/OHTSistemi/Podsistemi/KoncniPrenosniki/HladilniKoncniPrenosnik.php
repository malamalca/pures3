<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Izbire\VrstaHidravlicnegaUravnotezenja;
use App\Lib\Calc;

abstract class HladilniKoncniPrenosnik extends KoncniPrenosnik
{
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

        $this->hidravlicnoUravnotezenje =
            VrstaHidravlicnegaUravnotezenja::from($config->hidravlicnoUravnotezenje ?? 'neuravnotezeno');

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // VREDNOSTI deltaT glede na podizbire
        $this->deltaT_hydr = $this->hidravlicnoUravnotezenje->deltaTHydr($this);
    }

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function potrebnaElektricnaEnergija2($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $generator = array_first($sistem->generatorji);

        if (!empty($this->mocAux) && !empty($generator->nazivnaMoc)) {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
                $stUr = $stDni * 24;

                $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
                $stUr = 24 * $stDni;
                $potrebnaEnergija = $cona->energijaHlajenje[$mesec] + $cona->energijaRazvlazevanje[$mesec];
                $this->stUrDelovanjaNaDan[$mesec] = ceil($potrebnaEnergija / $stDni / $generator->nazivnaMoc);

                if ($vneseneIzgube[$mesec] > 0) {
                    $this->potrebnaElektricnaEnergija[$mesec] =
                        $this->stUrDelovanjaNaDan[$mesec] * $stDni * $this->mocAux * 0.001;
                } else {
                    $this->potrebnaElektricnaEnergija[$mesec] = 0;
                }
            }
        } else {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $this->potrebnaElektricnaEnergija[$mesec] = 0;
            }
        }

        return $this->potrebnaElektricnaEnergija;
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
