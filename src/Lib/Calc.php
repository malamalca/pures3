<?php
declare(strict_types=1);

namespace App\Lib;

class Calc
{
    public const MESECI = ['jan', 'feb', 'mar', 'apr', 'maj', 'jun', 'jul', 'avg', 'sep', 'okt', 'nov', 'dec'];
    public const NIX = 0.000001;

    /**
     * Izračun nasičenega tlaka glede na podano temperaturo
     *
     * @param float $T Temperatura
     * @return float
     */
    public static function nasicenTlak($T)
    {
        if ($T < 0) {
            return 610.5 * pow(M_E, 21.875 * $T / (265.5 + $T));
        } else {
            return 610.5 * pow(M_E, 17.269 * $T / (237.3 + $T));
        }
    }

    /**
     * Določi, če je podani mesec ogrevan ali ne
     *
     * @param int $mesec Številka meseca 0..122
     * @return bool
     */
    public static function jeMesecBrezOgrevanja($mesec)
    {
        return $mesec > 2 && $mesec < 9;
    }

    /**
     * Funkcija združi dve dR vrednosti
     *
     * @param \App\Calc\Hrup\Elementi\Konstrukcija $konstrukcija1 Prva konstrukcija
     * @param string $idSloja1 Id sloja 1
     * @param \App\Calc\Hrup\Elementi\Konstrukcija $konstrukcija2 Druga konstrukcija
     * @param string $idSloja2 Id sloja 2
     * @return float
     */
    public static function combineDeltaR($konstrukcija1, $idSloja1, $konstrukcija2, $idSloja2)
    {
        $sloj1 = array_first($konstrukcija1->dodatniSloji, fn($sloj) => $sloj->id == $idSloja1);
        $sloj2 = array_first($konstrukcija2->dodatniSloji, fn($sloj) => $sloj->id == $idSloja2);

        if (empty($sloj1->dR) && empty($sloj2->dR)) {
            return 0;
        }
        if (empty($sloj1->dR) && !empty($sloj2->dR)) {
            return $sloj2->dR;
        }
        if (!empty($sloj1->dR) && empty($sloj2->dR)) {
            return $sloj1->dR;
        }

        if ($sloj1->dR > $sloj2->dR) {
            return $sloj1->dR + $sloj2->dR / 2;
        } else {
            return $sloj2->dR + $sloj1->dR / 2;
        }
    }
}
