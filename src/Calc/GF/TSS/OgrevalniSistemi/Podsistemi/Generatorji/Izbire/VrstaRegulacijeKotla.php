<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Izbire;

enum VrstaRegulacijeKotla: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case KonstantnaTemperatura = 'konstantnaTemperatura';
    case SpremenljivaTemperatura = 'spremenljivaTemperatura';

    /**
     * Friendly name
     *
     * @return string
     */
    public function toString()
    {
        $lookup = [
            'Konstantna temperatura',
            'Spremenljiva temperatura',
        ];

        return $lookup[$this->getOrdinal()];
    }

    /**
     * Korekcijski faktor za upoštevanje vrste regulacije fc.
     * Tabela 12
     *
     * @param int $mesec Mesec
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return float
     */
    public function faktorRegulacije($mesec, $cona, $okolje)
    {
        if ($this == VrstaRegulacijeKotla::KonstantnaTemperatura) {
            return 0;
        } else {
            return ($okolje->zunanjaT[$mesec] - $okolje->projektnaZunanjaT) /
                ($cona->notranjaTOgrevanje / $okolje->projektnaZunanjaT);
        }
    }
}
