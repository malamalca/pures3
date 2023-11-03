<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\Izbire;

enum VrstaRegulacijeTemperature: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case CentralnaRegulacija = 'centralna';
    case ReferencniProstor = 'referencniProstor';
    case P_krmilnik = 'P-krmilnik';
    case PI_krmilnik = 'PI-krmilnik';
    case PI_krmilnikZOptimizacijo = 'PI-krmilnikZOptimizacijo';

    case TermostatNaPeci = 'termostatNaPeci';
    case SobniTermostat = 'sobniTermostat';

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
            'Termostat na peči',
            'Sobni termostat',
        ];

        return $lookup[$this->getOrdinal()];
    }

    /**
     * Delta T za neposredno električno ogrevanje
     *
     * @return float
     */
    public function deltaTElektricnoOgrevalo()
    {
        if ($this == VrstaRegulacijeTemperature::P_krmilnik) {
            return 1.1;
        }

        if ($this == VrstaRegulacijeTemperature::PI_krmilnik) {
            return 0.7;
        }

        throw new \Exception(sprintf('Regulacija "%s" za električno ogrevalo ni podprta', $this->toString()));
    }

    /**
     * Delta Tctr za peč na drva, kamine,..
     *
     * @return float
     */
    public function deltaTCtrPecNaDrva()
    {
        if ($this == VrstaRegulacijeTemperature::TermostatNaPeci) {
            return 2.5;
        }

        if ($this == VrstaRegulacijeTemperature::SobniTermostat) {
            return 2;
        }

        throw new \Exception(sprintf('Regulacija "%s" za peč na drva ni podprta', $this->toString()));
    }
}
