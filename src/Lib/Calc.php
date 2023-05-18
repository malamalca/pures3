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
}
