<?php
declare(strict_types=1);

namespace App\Calc\GF\Stavbe\Izbire;

enum VrstaGradnje: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Nova = 'nova';
    case Rekonstrukcija = 'rekonstrukcija';
    case CelovitaObnova = 'celovitaObnova';

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
