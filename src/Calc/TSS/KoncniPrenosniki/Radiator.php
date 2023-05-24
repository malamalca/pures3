<?php

namespace App\Calc\TSS\KoncniPrenosniki;

use App\Lib\Calc;

enum VrsteNamestitve: string {
    use \App\Lib\Traits\GetOrdinalTrait;

    case ObNotranjiSteni = 'notranjeStene';
    case ObZunanjemZidu = 'zunanjeStene';
    case ObZunanjemZiduZasteklitevBrezSevalneZascite = 'zasteklitevBrezZascite';
    case ObZunanjemZiduZasteklitevSSevalnoZascite = 'zasteklitevZZascito';
}

class Radiator extends KoncniPrenosnik {
    const DELTAT_REZIM = [0.4, 0.5, 0.7];
    const DELTAT_NAMESTITEV = [1.3, 0.3, 1.7, 1.2];

    public $exponentOgrevala = 1.33;

    protected VrsteNamestitve $namestitev;

    public function parseConfig($config)
    {
        parent::parseConfig($config);
        
        $this->namestitev = VrsteNamestitve::from($config->namestitev);
    }

    public function toplotneIzgube($cona, $okolje)
    {
        // Δθhydr - deltaTemp za hidravlično uravnoteženje sistema; prvi stolpec za stOgreval <= 10, drugi za > 10
        $deltaT_hydr = parent::DELTAT_HIDRAVLICNEGA_URAVNOTEZENJA_DO_10[$this->hidravlicnoUravnotezenje->getOrdinal()];

        // Δθctr - deltaTemp za regulacijo temperature; prvi stolpec sevala, drugi stolpec toplovod, h<4m
        $deltaT_ctr = parent::DELTAT_REGULACIJE_TEMPERATURE[$this->regulacijaTemperature->getOrdinal()];

        // Δθemb - deltaTemp za izolacijo (polje R206)
        $deltaT_emb = 0;

        // Δθstr - deltaTemp Str (polje Q208)
        $deltaT_str = self::DELTAT_NAMESTITEV[$this->namestitev->getOrdinal()] +
            self::DELTAT_REZIM[$this->rezim->getOrdinal()];

        $deltaT = $deltaT_hydr + $deltaT_ctr + $deltaT_emb + $deltaT_str;

        $toplotneIzgube = [];
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $faktorDeltaT = $deltaT / ($cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec]);
            $toplotneIzgube[$mesec] = $cona->energijaOgrevanje[$mesec] * $faktorDeltaT;
        }

        return $toplotneIzgube;
    }
}