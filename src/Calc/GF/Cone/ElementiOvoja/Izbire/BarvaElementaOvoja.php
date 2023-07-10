<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\ElementiOvoja\Izbire;

enum BarvaElementaOvoja: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Notranja = 'brez';
    case Svetla = 'svetla';
    case Temnejska = 'temnejsa';
    case AmorfniSilicij = 'temna';

    /**
     * Vrne koeficient $alphaSr glede na barvo
     *
     * @return float
     */
    public function koeficientAlphaSr()
    {
        $KLookup = [0, 0.3, 0.6, 0.9];

        return $KLookup[$this->getOrdinal()];
    }
}
