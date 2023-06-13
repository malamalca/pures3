<?php
declare(strict_types=1);

namespace App\Calc\TSS\PrezracevalniSistemi\Izbire;

enum VrstaTransportaZraka: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Dovod = 'dovod';
    case Odvod = 'odvod';

    /**
     * Faktor Spf glede na vrsto transporta
     *
     * @return float
     */
    public function faktorSpf()
    {
        $lookup = [0.211, 0.142];

        return $lookup[$this->getOrdinal()];
    }
}
