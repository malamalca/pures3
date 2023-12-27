<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\Izbire;

enum VrstaIzpostavljenostiFasad: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case VecFasad = 'izpostavljenaEnaFasada';
    case EnaFasada = 'izpostavljenihVecFasad';

    /**
     * Vrne faktor vetra po tabeli 8.8
     *
     * @return float
     */
    public function faktorVetra()
    {
        $k = [15, 20];

        return $k[$this->getOrdinal()];
    }

    /**
     * Vrne sifro za EI XML
     *
     * @return int
     */
    public function sifraEI()
    {
        $sifre = [1, 2];

        return $sifre[$this->getOrdinal()];
    }
}
