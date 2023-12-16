<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\Izbire;

enum VrstaLegeStavbe: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Izpostavljena = 'izpostavljena';
    case DelnoIzpostavljena = 'delnoIzpostavljena';
    case Neizpostavljena = 'neizpostavljena';

    /**
     * Vrne koeficient vpliva vetra po tabeli 8.8
     *
     * @param \App\Calc\GF\Cone\Izbire\VrstaIzpostavljenostiFasad $zavetrovanje Zavetrovanje
     * @return float
     */
    public function koeficientVplivaVetra(VrstaIzpostavljenostiFasad $zavetrovanje)
    {
        $k = [
            VrstaIzpostavljenostiFasad::EnaFasada->getOrdinal() => [0.03, 0.02, 0.01],
            VrstaIzpostavljenostiFasad::VecFasad->getOrdinal() => [0.1, 0.07, 0.04],
        ];

        return $k[$zavetrovanje->getOrdinal()][$this->getOrdinal()];
    }
}
