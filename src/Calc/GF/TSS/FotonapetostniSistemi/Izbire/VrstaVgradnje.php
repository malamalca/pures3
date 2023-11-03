<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\FotonapetostniSistemi\Izbire;

enum VrstaVgradnje: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Neprezracevani = 'neprezracavani';
    case ZmernoPrezracevani = 'zmernoPrezracevani';
    case DobroPrezracevani = 'dobroPrezracevani';

    /**
     * Vrne koeficient glede na vrsto vgradnje
     *
     * @return float
     */
    public function koeficientVgradnje()
    {
        $KpfLookup = [0.78, 0.8, 0.82];

        return $KpfLookup[$this->getOrdinal()];
    }
}
