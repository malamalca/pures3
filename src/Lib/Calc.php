<?php
declare(strict_types=1);

namespace App\Lib;

class Calc
{
    public const MESECI = ['jan', 'feb', 'mar', 'apr', 'maj', 'jun', 'jul', 'avg', 'sep', 'okt', 'nov', 'dec'];
    public const NIX = 0.000001;
    public const HITROST_ZVOKA = 343;
    public const GOSTOTA_ZRAKA = 1.18;

    public const FREKVENCE_TERCE = [
        //50, 63, 80,
        100, 125, 160,
        200, 250, 315,
        400, 500, 630,
        800, 1000, 1250,
        1600, 2000, 2500,
        3150,
        //4000, 5000,
    ];

    public const RF = [
        //null, null, null,
        33, 36, 39,
        42, 45, 48,
        51, 52, 53,
        54, 55, 56,
        56, 56, 56,
        56,
        //null, null,
    ];

    public const SPQ_C = [
        29, 26, 23,
        21, 19, 17,
        15, 13, 12,
        11, 10, 9,
        9, 9, 9,
        9,
    ];
    public const SPQ_CTR = [
        20, 20, 18,
        16, 15, 14,
        13, 12, 11,
        9, 8, 9,
        10, 11, 13,
        15,
    ];

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
        $sloj1 = array_first(
            $konstrukcija1->dodatniSloji,
            fn($sloj) => isset($sloj->id) ? $sloj->id == $idSloja1 : false
        );
        $sloj2 = array_first(
            $konstrukcija2->dodatniSloji,
            fn($sloj) => isset($sloj->id) ? $sloj->id == $idSloja2 : false
        );

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

    /**
     * Iz izolativnosti po frekvencah izračuna enoštevilčno izolativnost
     *
     * @param array $R Izolativnost po frekvencah
     * @return int
     */
    // phpcs:ignore
    public static function Rw(array $R)
    {
        if (count($R) == 1) {
            return $R[500];
        }

        if (count($R) == 16) {
            // 717-1
            $shift = -40;
            $shiftSum = 0;

            $R = array_values($R);

            while (($shift < 40) && ($shiftSum < 32)) {
                $shiftSum = 0;
                foreach (self::FREKVENCE_TERCE as $ix => $fq) {
                    //var_dump($R[$ix] . ' | ' . (self::RF[$ix] + $shift) . ' | ' . ((($R[$ix] - (self::RF[$ix] + $shift)) < 0) ? (-$R[$ix] + (self::RF[$ix] + $shift)) : ''));
                    if ($R[$ix] - (self::RF[$ix] + $shift) < 0) {
                        $shiftSum += (-$R[$ix] + self::RF[$ix] + $shift);
                    }
                }

                $shift++;
            }

            $result = 52 + $shift - 2;

            return $result;
        }

        return -1;
    }

    /**
     * Iz izolativnosti po frekvencah izračuna C
     *
     * @param array $R Izolativnost po frekvencah
     * @param float $povrsinskaMasa Površinska masa elementa
     * @return float
     */
    // phpcs:ignore
    public static function C(array $R, float $povrsinskaMasa = 0)
    {
        if (count($R) == 1) {
            $C = $povrsinskaMasa > 200 ? -2 : -1;

            return $C;
        }
        if (count($R) == 16) {
            $sumTau = 0;
            $R = array_values($R);

            foreach (self::FREKVENCE_TERCE as $ix => $fq) {
                $sumTau += pow(10, (-self::SPQ_C[$ix] - $R[$ix]) / 10);
            }

            $C = -(self::Rw($R) - round((-10 * log10($sumTau)), 0));

            return $C;
        }

        return -1;
    }

    /**
     * Iz izolativnosti po frekvencah izračuna Ctr
     *
     * @param array $R Izolativnost po frekvencah
     * @param float $povrsinskaMasa Površinska masa elementa
     * @return float
     */
    // phpcs:ignore
    public static function Ctr(array $R, float $povrsinskaMasa = 0)
    {
        if (count($R) == 1) {
            $Ctr = round(16 - 9 * log10($povrsinskaMasa), 0);
            if ($Ctr > -1) {
                $Ctr = -1;
            }
            if ($Ctr < -7) {
                $Ctr = -7;
            }

            return $Ctr;
        }
        if (count($R) == 16) {
            $sumTau = 0;
            $R = array_values($R);

            foreach (self::FREKVENCE_TERCE as $ix => $fq) {
                $sumTau += pow(10, (-self::SPQ_CTR[$ix] - $R[$ix]) / 10);
            }

            $Ctr = -(self::Rw($R) - round((-10 * log10($sumTau)), 0));

            return $Ctr;
        }

        return -1;
    }
}
