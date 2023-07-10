<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\ElementiOvoja\Izbire;

enum VrstaTal: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Glina = 'glina';
    case Pesek = 'pesek';
    case Kamen = 'kamen';

    /**
     * Vrne lambdo glede na tip tal
     *
     * @return float
     */
    public function lambda()
    {
        $KLookup = [1.5, 2, 3.5];

        return $KLookup[$this->getOrdinal()];
    }

    /**
     * Vrne produkt ro*c
     *
     * @return int
     */
    public function roC()
    {
        $KLookup = [3000000, 2000000, 2000000];

        return $KLookup[$this->getOrdinal()];
    }

    /**
     * Vrne sigmo glede na tip tal
     * Periodična debelina konstrukcije sigma C.1
     * ne rabimo računati, imamo lookup v tabeli standarda
     * po standardu: $sigma = sqrt(3.15 * pow(10, 7) * $tla->lambda() / (pi() * $lastnostiTal['ro*c']));
     *
     * @return float
     */
    public function sigma()
    {
        $KLookup = [2.2, 3.2, 4.2];

        return $KLookup[$this->getOrdinal()];
    }
}
