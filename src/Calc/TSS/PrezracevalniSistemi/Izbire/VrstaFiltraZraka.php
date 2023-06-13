<?php
declare(strict_types=1);

namespace App\Calc\TSS\PrezracevalniSistemi\Izbire;

enum VrstaFiltraZraka: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case BrezFiltra = 'brez';
    case HepaFilter = 'hepa';
    case FFilter = 'f';

    /**
     * Dodatek volumnu glede na vrsto filtra
     *
     * @return float
     */
    public function dodatekFiltra()
    {
        $lookup = [0, 1000, 300];

        return $lookup[$this->getOrdinal()];
    }
}
