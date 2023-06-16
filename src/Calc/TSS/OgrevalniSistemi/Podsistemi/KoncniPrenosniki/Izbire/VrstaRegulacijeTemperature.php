<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\Izbire;

enum VrstaRegulacijeTemperature: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case CentralnaRegulacija = 'centralna';
    case ReferencniProstor = 'referencniProstor';
    case P_krmilnik = 'P-krmilnik';
    case PI_krmilnik = 'PI-krmilnik';
    case PI_krmilnikZOptimizacijo = 'PI-krmilnikZOptimizacijo';

    /**
     * Friendly name
     *
     * @return string
     */
    public function toString()
    {
        $lookup = [
            'Centralna regulacija temperature',
            'S temperaturo referenčnega prostora',
            'P-krmilnik',
            'PI-krmilnik',
            'PI-krmilnik z algoritmom optimizacije',
        ];

        return $lookup[$this->getOrdinal()];
    }
}
