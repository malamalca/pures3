<?php
declare(strict_types=1);

namespace App\Calc\Hrup\Elementi\Izbire;

use App\Lib\Calc;
use stdClass;

enum TipKonstrukcije: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    private const DOLZINA_ROBA_1 = 4;
    private const DOLZINA_ROBA_2 = 3;

    case Enostavna = 'enostavna';
    case Zahtevna = 'zahtevna';

    /**
     * Izračun R
     *
     * @param float $povrsinskaMasa Površina osnovne konstrukcije
     * @param \stdClass|null $lastnosti Lastnosti materiala
     * @return array
     */
    public function R(float $povrsinskaMasa, ?stdClass $lastnosti)
    {
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName
        $R = [];

        if ($this == TipKonstrukcije::Enostavna) {
            $R = [500 => round(37.5 * log10($povrsinskaMasa) - 42, 0)];
        }

        if ($this == TipKonstrukcije::Zahtevna) {
            if (empty($lastnosti->debelina)) {
                $lastnosti->debelina = $povrsinskaMasa / $lastnosti->gostota;
            } elseif (empty($lastnosti->gostota)) {
                $lastnosti->gostota = $povrsinskaMasa / $lastnosti->debelina;
            }
            // enačbe
            // E - young's modulus
            // µ - Poisson’s ratio
            // h - debelina
            // Bp is the bending stiffness per unit width for a single-leaf (Nm)

            // The speed of sound for longitudinal waves, cL
            // cL = sqrt(E / (m' * (1 - µ^2)))

            // The speed of sound for bending waves cB
            // cB = sqrt(2*π*f) * pow(E * h^2 / (12 * m' * (1 - µ^2)), 1/4)

            // kritična frekvenca
            // $fc = c0^2 / (2*π) * sqrt(m' / Bp) = c0^2 / π * sqrt(3 * m' * (1 - µ^2) / (E * h^3))

            // imam dve možnosti za izračun kritične frekvence:
            // 1. če imam podano hitrostLongitudinalnihValov
            // 2. če nimam podane hitrostLongitudinalnihValov, potem potrebujem Youngov Modul in Poissonov količnik

            if (empty($lastnosti->hitrostLongitudinalnihValov)) {
                $lastnosti->hitrostLongitudinalnihValov =
                    sqrt($lastnosti->E / ($lastnosti->gostota * (1.0 - pow($lastnosti->poi, 2))));
            }

            // kritična frekvenca po ISO 12354-1
            $fcrit = pow(Calc::HITROST_ZVOKA, 2) /
                (Pi() / sqrt(3) * $lastnosti->hitrostLongitudinalnihValov * $lastnosti->debelina);

            // kritična frekvenca po literaturi
            // $E = 26 * pow(10, 9);  // young [N/m2]
            // $poi = 0.2; // poisson []
            // $B = ($E * pow($lastnosti->debelina, 3)) / (12.0 * (1.0 - pow($poi, 2)));     // Rigidez del material [Nm^2]
            // $fc = pow(Calc::HITROST_ZVOKA, 2) / (2 * pi()) * sqrt($povrsinskaMasa / $B);

            $f11 = pow(Calc::HITROST_ZVOKA, 2) / (4 * $fcrit) *
                ((1 / pow(self::DOLZINA_ROBA_1, 2)) + (1 / pow(self::DOLZINA_ROBA_2, 2)));

            // frekvenca platoja
            $fp = $lastnosti->hitrostLongitudinalnihValov / (5.5 * $lastnosti->debelina);

            // izračun faktorja sevanja "radiation factor"
            foreach (Calc::FREKVENCE_TERCE as $fq) {
                // valovno število v radianih na m
                $k0 = 2 * pi() * $fq / Calc::HITROST_ZVOKA;

                // enačba B.2 spodaj
                $A = -0.964 - (0.5 + (self::DOLZINA_ROBA_2 / (pi() * self::DOLZINA_ROBA_1))) *
                    log(self::DOLZINA_ROBA_2 / self::DOLZINA_ROBA_1) +
                    (5 * self::DOLZINA_ROBA_2 / (2 * pi() * self::DOLZINA_ROBA_1)) -
                    (1 / (4 * pi() * self::DOLZINA_ROBA_1 * self::DOLZINA_ROBA_2 * pow($k0, 2)));

                // enačba B.2
                $faktorSevanja = 0.5 * (log($k0 * sqrt(self::DOLZINA_ROBA_1 * self::DOLZINA_ROBA_2)) - $A);
                if ($faktorSevanja > 2) {
                    $faktorSevanja = 2;
                }

                // Da bi upoštevali tudi druge vrste valov, ki so relevantni pri debelih stenah in/ali pri višjih frekvencah,
                // se nad kritično frekvenco ta frekvenca pri računu nadomesti z efektivno kritično frekvenco
                $fc = $fcrit;
                if ($fq > $fcrit && $fq < $fp) {
                    $fc = $fcrit * (4.05 * ($lastnosti->debelina * $fq / $lastnosti->hitrostLongitudinalnihValov) +
                        sqrt(1 + (4.05 * $lastnosti->debelina * $fq / $lastnosti->hitrostLongitudinalnihValov)));
                } elseif ($fq > $fcrit && $fq >= $fp) {
                    $fc = 2 * $fcrit * pow($fq / $fp, 3);
                }

                // SIGMA = faktor sevanja za proste upogibne valove
                // enačbe B.3a
                $sigma1 = 1 / sqrt(1 - $fc / $fq);

                $sigma2 = 4 * self::DOLZINA_ROBA_1 * self::DOLZINA_ROBA_2 * pow($fq / Calc::HITROST_ZVOKA, 2);
                $sigma3 =
                    sqrt((2 * pi() * $fq * (self::DOLZINA_ROBA_1 + self::DOLZINA_ROBA_2)) / (16 * Calc::HITROST_ZVOKA));
                //$f11 = pow(Calc::HITROST_ZVOKA, 2) / (4 * $fc) * (1 / pow(self::DOLZINA_ROBA_1, 2) +
                //    1 / pow(self::DOLZINA_ROBA_2, 2));

                // enačba B.3b posebej
                $lambda = sqrt($fq / $fc);

                $delta1 = (((1 - pow($lambda, 2)) * log((1 + $lambda) / (1 - $lambda)) + 2 * $lambda)) /
                    (4 * pow(pi(), 2) * pow(1 - pow($lambda, 2), 1.5));

                if ($fq > $fc / 2) {
                    $delta2 = 0;
                } else {
                    $delta2 = (8 * pow(Calc::HITROST_ZVOKA, 2) * (1 - 2 * pow($lambda, 2))) /
                        (pow($fc, 2) * pow(pi(), 4) * self::DOLZINA_ROBA_1 * self::DOLZINA_ROBA_2 *
                        $lambda * sqrt(1 - pow($lambda, 2)));
                }

                $sigma4 = 2 * (self::DOLZINA_ROBA_1 + self::DOLZINA_ROBA_2) /
                    (self::DOLZINA_ROBA_1 * self::DOLZINA_ROBA_2) *
                    Calc::HITROST_ZVOKA / $fc * $delta1 + $delta2;

                if ($f11 <= $fc / 2) {
                    if ($fq >= $fc) {
                        $sigma = $sigma1;
                    } else {
                        $sigma = $sigma4;
                    }
                    if ($f11 > $fq && $sigma > $sigma2) {
                        $sigma = $sigma2;
                    }
                } else {
                    if ($fq < $fc && $sigma2 < $sigma3) {
                        $sigma = $sigma2;
                    } elseif ($fq > $fc && $sigma1 < $sigma3) {
                        $sigma = $sigma1;
                    } else {
                        $sigma = $sigma3;
                    }
                }

                if ($sigma > 2) {
                    $sigma = 2;
                }

                // Odmevni čas elementa

                // SKUPNI FAKTOR IZGUB
                // enačba C.5
                $ntot = $lastnosti->faktorNotranjegaDusenja + ($povrsinskaMasa / (485 * sqrt($fq)));

                // enačba C.4
                /*$X = sqrt(31.1 / $fcrit);
                $Y = 44.3 * ($fcrit / $povrsinskaMasa);

                // koeficient absorpcije pri spoju k celotne talne plošče
                $alpha = 1/3 * pow((2 * sqrt($X * $Y) * (1 + $X) * (1 + $Y)) / ($X * pow(1 + $Y, 2)+ 2 * $Y * (1 + pow($X, 2))), 2);
                $alphak = $alpha * (1 - 0.9999 * $alpha);

                $S = $lastnosti->debelina * self::DOLZINA_ROBA_1;

                // enačba C.1
                $ntot2 = $this->faktorNotranjegaDusenja + ((2 * Calc::GOSTOTA_ZRAKA * Calc::HITROST_ZVOKA * $sigma) /
                    (2 * pi() * $fq * $this->povrsinskaMasa)) +
                    (Calc::HITROST_ZVOKA / (pow(pi(), 2) * $S * sqrt($fq * $fc)) *
                    (((2 * $this->debelina + 2 * self::DOLZINA_ROBA_1) * $alphak) * 2 +
                    ((2 * $this->debelina + 2 * self::DOLZINA_ROBA_2) * $alphak) * 2));*/

                // enačba B.1
                if ($fq > ($fc + 0.15 * $fc)) {
                    $tau = (pow((2 * Calc::GOSTOTA_ZRAKA * Calc::HITROST_ZVOKA) /
                        (2 * pi() * $fq * $povrsinskaMasa), 2)) *
                        (pi() * $fc * pow($sigma, 2)) / (2 * $fq * $ntot);
                } elseif ($fq >= ($fc - 0.15 * $fc) && $fq <= ($fc + 0.1 * $fc)) {
                    $tau = (pow((2 * Calc::GOSTOTA_ZRAKA * Calc::HITROST_ZVOKA) /
                        (2 * pi() * $fq * $povrsinskaMasa), 2)) *
                        pi() * pow($sigma, 2) / (2 * $ntot);
                } else {
                    /*if ($fq < ($fc - 0.15 * $fc))*/
                    $tau = (pow((2 * Calc::GOSTOTA_ZRAKA * Calc::HITROST_ZVOKA) /
                        (2 * pi() * $fq * $povrsinskaMasa), 2)) *
                        (2 * $faktorSevanja + (pow(self::DOLZINA_ROBA_1 + self::DOLZINA_ROBA_2, 2)) /
                        (pow(self::DOLZINA_ROBA_1, 2) + pow(self::DOLZINA_ROBA_2, 2)) *
                        sqrt($fc / $fq) * pow($sigma, 2) / $ntot);
                }

                $R[$fq] = -10 * log10($tau);
            }
        }

        return $R;
    }

    /**
     * Izračun Rw
     *
     * @param float $povrsinskaMasa Površina osnovne konstrukcije
     * @param array $R Izolativnost po frekvencah
     * @return float
     */
    public function Rw(float $povrsinskaMasa, array $R)
    {
        $Rw = 0;

        if ($this == TipKonstrukcije::Enostavna) {
            $Rw = $R[500];
        }

        if ($this == TipKonstrukcije::Zahtevna) {
            $Rw = Calc::Rw($R);
        }

        return $Rw;
    }

    /**
     * Izračun Faktorja C
     *
     * @param float $povrsinskaMasa Površina osnovne konstrukcije
     * @param array $R Izolativnost po frekvencah
     * @return float
     */
    public function C(float $povrsinskaMasa, array $R = [])
    {
        $C = 0;

        if ($this == TipKonstrukcije::Enostavna) {
            $C = $povrsinskaMasa > 200 ? -2 : -1;
        }

        if ($this == TipKonstrukcije::Zahtevna) {
            $C = Calc::C($R);
        }

        return $C;
    }

    /**
     * Izračun Faktorja Ctr
     *
     * @param float $povrsinskaMasa Površina osnovne konstrukcije
     * @param array $R Izolativnost po frekvencah
     * @return float
     */
    public function Ctr(float $povrsinskaMasa, array $R = [])
    {
        $Ctr = 0;

        if ($this == TipKonstrukcije::Enostavna) {
            $Ctr = round(16 - 9 * log10($povrsinskaMasa), 0);
            if ($Ctr > -1) {
                $Ctr = -1;
            }
            if ($Ctr < -7) {
                $Ctr = -7;
            }
        }

        if ($this == TipKonstrukcije::Zahtevna) {
            $Ctr = Calc::Ctr($R);
        }

        return $Ctr;
    }

    /**
     * Izračun Lnw
     *
     * @param float $povrsinskaMasa Površina osnovne konstrukcije
     * @param array $R Izolativnost po frekvencah
     * @return float
     */
    public function Lnw(float $povrsinskaMasa, array $R = [])
    {
        $Lnw = 0;

        if ($this == TipKonstrukcije::Enostavna) {
            $Lnw = 164 - 35 * log10($povrsinskaMasa);
        }

        if ($this == TipKonstrukcije::Zahtevna) {
        }

        return $Lnw;
    }

    /**
     * Vrne naziv tipa konstrukcije
     *
     * @return string
     */
    public function naziv()
    {
        $nazivi = ['Enostavna Konstrukcija', 'Zahtevna Konstrukcija'];

        return $nazivi[$this->getOrdinal()];
    }
}
