<?php
declare(strict_types=1);

namespace App\Calc\GF\Stavbe\Izbire;

enum VrstaZahtevnosti: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Nezahtevna = 'nezahtevna';
    case ManjZahtevna = 'manjzahtevna';
    case Zahtevna = 'zahtevna';

    /**
     * Vrne sifro za EI XML
     *
     * @return int
     */
    public function sifraEI()
    {
        $sifre = [1, 2, 3];

        return $sifre[$this->getOrdinal()];
    }
}
