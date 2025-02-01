<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Izbire;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\KoncniPrenosnik;

enum VrstaHidravlicnegaUravnotezenja: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Neuravnotezeno = 'neuravnotezeno';
    case StaticnoUravnotezenjeKoncnihPrenosnikov = 'staticnoKoncnihPrenosnikov';
    case StaticnoUravnotezenjeDviznihVodov = 'staticnoDviznihVodov';
    case DinamicnoUravnotezenjePolnaObremenitev = 'dinamicnoPolnaObremenitev';
    case DinamicnoUravnotezenjeDelnaObremenitev = 'dinamicnoDelnaObremenitev';

    /**
     * Friendly name
     *
     * @return string
     */
    public function toString()
    {
        $lookup = [
            'Neuravnoteženo',
            'Statično uravnotezenje končnih prenosnikov',
            'Statično uravnoteženje dvižnih vodov',
            'Dinamično uravnoteženje pri polni obremenitvi',
            'Dinamično uravnoteženje pri delni obremenitvi',
        ];

        return $lookup[$this->getOrdinal()];
    }

    /**
     * Δθhydr - deltaTemp za hidravlično uravnoteženje sistema; prvi stolpec za stOgreval <= 10, drugi za > 10
     * temperature variation based on not balanced hydraulic systems (K)
     *
     * @param \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\KoncniPrenosnik $prenosnik Končni prenosnik
     * @return float
     */
    public function deltaTHydr(KoncniPrenosnik $prenosnik): float
    {
        switch (get_class($prenosnik)) {
            case \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Hlajenje\StenskiKonvektor::class:
            case \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Hlajenje\StropniKonvektor::class:
                $do10Prenosnikov = [-0.6, -0.3, -0.2, -0.1, -0.0];
                $nad10Prenosnikov = [-0.6, -0.4, -0.3, -0.2, -0.0];

                if ($prenosnik->stevilo <= 10) {
                    return $do10Prenosnikov[$this->getOrdinal()];
                } else {
                    return $nad10Prenosnikov[$this->getOrdinal()];
                }
            case \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\PloskovnoOgrevalo::class:
            case \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Radiator::class:
            case \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Konvektor::class:
                $do10Prenosnikov = [0.6, 0.3, 0.2, 0.1, 0];
                $nad10Prenosnikov = [0.6, 0.4, 0.3, 0.2, 0];

                if ($prenosnik->stevilo <= 10) {
                    return $do10Prenosnikov[$this->getOrdinal()];
                } else {
                    return $nad10Prenosnikov[$this->getOrdinal()];
                }
            default:
                throw new \Exception(sprintf('Unknown Class "%s" for deltaTHydr()', get_class($prenosnik)));
        }
    }
}
