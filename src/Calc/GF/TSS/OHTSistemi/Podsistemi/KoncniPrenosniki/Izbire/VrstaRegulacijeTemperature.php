<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Izbire;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\KoncniPrenosnik;

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

    // P controller (proportional controller)- typically thermostatic controlled valves (V)
    // PI controller (proportional integral controller)– typically electronic controller
    // P-controllers are usually directly placed on the emitter (e.g. radiator),
    // PI-controller and “room temperature controlled” in accordance to table 1 can also be installed on a surrounding wall of the room.

    /**
     * Friendly name
     *
     * @return string
     */
    public function toString()
    {
        $lookup = [
            'Centralna regulacija temperature',
            'Temperatura referenčnega prostora',
            'P-krmilnik',
            'PI-krmilnik',
            'PI-krmilnik z algoritmom optimizacije',
            'Termostat na peči',
            'Sobni termostat',
        ];

        return $lookup[$this->getOrdinal()];
    }

    /**
     * Δθctr - deltaTemp za regulacijo temperature; prvi stolpec sevala, drugi stolpec toplovod, h<4m
     * temperature variation based on control variation
     *
     * @param \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\KoncniPrenosnik Končni prenosnik
     * @return float
     */
    public function deltaTCtr(KoncniPrenosnik $prenosnik)
    {
        switch (get_class($prenosnik)) {
            case \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Hlajenje\StenskiKonvektor::class:
            case \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Hlajenje\StropniKonvektor::class:
                $ret = [-2.5, -1.8, -0.7, -0.7, -0.5, -1.6];

                return $ret[$this->getOrdinal()];
            case \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\PloskovnoOgrevalo::class:
            case \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Radiator::class:
            case \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Konvektor::class:
                $ret = [2.5, 1.6, 0.7, 0.7, 0.5];

                return $ret[$this->getOrdinal()];
                
            case \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\ElektricnoOgrevalo::class:
                if ($this == VrstaRegulacijeTemperature::P_krmilnik) {
                    return 1.5;
                }
        
                if ($this == VrstaRegulacijeTemperature::PI_krmilnik) {
                    return 1.1;
                }
        
                throw new \Exception(sprintf('Regulacija "%s" za električno ogrevalo ni podprta', $this->toString()));
            case \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\PecNaDrva::class:
                if ($this == VrstaRegulacijeTemperature::TermostatNaPeci) {
                    return 2.5;
                }
        
                if ($this == VrstaRegulacijeTemperature::SobniTermostat) {
                    return 2;
                }
        
                throw new \Exception(sprintf('Regulacija "%s" za peč na drva ni podprta', $this->toString()));
            default:
                throw new \Exception(sprintf('Unknown Class "%s" for deltaTCtr()', get_class($prenosnik)));
        }
    }
}