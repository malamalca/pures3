<?php
declare(strict_types=1);

namespace App\Calc\TSS\PrezracevalniSistemi\Izbire;

enum VrstaKrmiljenja: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case RocniVklop = 'rocniVklop';
    case Casovnik = 'casovnik';
    case CentralnaRegulacija = 'centralnaRegulacija';
    case LokalnaRegukacija = 'lokalnaRegulacija';

    /**
     * Vrne faktor krmiljenja
     *
     * @return float
     */
    public function faktorSistemaKrmiljenja()
    {
        $lookup = [1, 0.95, 0.85, 0.65];

        return $lookup[$this->getOrdinal()];
    }
}
