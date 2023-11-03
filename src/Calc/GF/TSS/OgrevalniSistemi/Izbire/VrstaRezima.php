<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Izbire;

use App\Calc\GF\TSS\OgrevalniSistemi\ToplovodniOgrevalniSistem;
use App\Calc\GF\TSS\TSSVrstaEnergenta;

enum VrstaRezima: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Rezim_35_30 = '35/30';
    case Rezim_40_30 = '40/30';
    case Rezim_55_45 = '55/45';

    /**
     * Vrne srednjo temperaturo režima
     *
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @return float
     */
    public function srednjaTemperatura($sistem)
    {
        if (
            $sistem instanceof ToplovodniOgrevalniSistem ||
            $sistem->energent == TSSVrstaEnergenta::Elektrika
        ) {
            // za ploskovno | toplovodno+elektrika
            $srednjeTemperature = [32.5, 35, 50];
        } else {
            // vse ostalo
            $srednjeTemperature = [35, 50, 62.5];
        }

        return $srednjeTemperature[$this->getOrdinal()];
    }

    /**
     * Vrne temperaturo ponora
     *
     * @return float
     */
    public function temperaturaPonora()
    {
        $temperaturePonora = [35, 40, 55];

        return $temperaturePonora[$this->getOrdinal()];
    }

    /**
     * Vrne faktor COP za toplotne črpalke glede na režim
     *
     * @return float
     */
    public function faktorDeltaTempTC()
    {
        return $this->getOrdinal() == 0 ? 1.02 : 1.051;
    }

    /**
     * ΔθHK – temperaturna razlika pri standardnem temperaturnem režimu ogrevalnega sistema [°C]
     *
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @return int
     */
    public function temperaturnaRazlikaStandardnegaRezima($sistem)
    {
        $deltaTHKLookup = [[5, 10], [10, 15], [10, 15]];

        if (
            $sistem instanceof ToplovodniOgrevalniSistem ||
            $sistem->energent == TSSVrstaEnergenta::Elektrika
        ) {
            $deltaT_HK = $deltaTHKLookup[$this->getOrdinal()][0];
        } else {
            $deltaT_HK = $deltaTHKLookup[$this->getOrdinal()][1];
        }

        return $deltaT_HK;
    }
}
