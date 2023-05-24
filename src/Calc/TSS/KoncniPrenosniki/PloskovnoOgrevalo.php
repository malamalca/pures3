<?php

namespace App\Calc\TSS\KoncniPrenosniki;

use App\Lib\Calc;

enum VrsteSistemovOgreval: string {
    use \App\Lib\Traits\GetOrdinalTrait;

    case TalnoOgrevanjeMokriSistem = 'talno_mokri';
    case TalnoOgrevanjeSuhiSistem = 'talno_suhi';
    case TalnoOgrevanjeSuhiSistemSTankoOblogo = 'talno_suhiTankaObloga';
    case StenskoOgrevanje = 'stensko';
    case StropnoOgrevanje = 'stopno';
}

enum VrsteIzolacij: string {
    use \App\Lib\Traits\GetOrdinalTrait;

    case BrezMinimalneIzolacije = 'brez';
    case MinimalnaIzolacija = 'min';
    case PovecanaIzolacija100Procentov = '100%';
}


class PloskovnoOgrevalo extends KoncniPrenosnik {
    const DELTAT_VRSTE_SISTEMOV = [0, 0, 0, 0.4, 0.7];
    const DELTAT_SPECIFICNIH_IZGUB = [1.4, 0.5, 0.1];

    public $exponentOgrevala = 1.1;
    public $deltaP_FBH = 25;

    protected VrsteSistemovOgreval $sistemOgreval;
    protected VrsteIzolacij $izolacija;

    public function parseConfig($config)
    {
        parent::parseConfig($config);

        $this->sistemOgreval = VrsteSistemovOgreval::from($config->sistem);
        $this->izolacija = VrsteIzolacij::from($config->izolacija);
    }

    public function toplotneIzgube($cona, $okolje)
    {
        // Δθhydr - deltaTemp za hidravlično uravnoteženje sistema; prvi stolpec za stOgreval <= 10, drugi za > 10
        $deltaT_hydr = parent::DELTAT_HIDRAVLICNEGA_URAVNOTEZENJA_DO_10[$this->hidravlicnoUravnotezenje->getOrdinal()];

        // Δθctr - deltaTemp za regulacijo temperature; prvi stolpec sevala, drugi stolpec toplovod, h<4m
        $deltaT_ctr = parent::DELTAT_REGULACIJE_TEMPERATURE[$this->regulacijaTemperature->getOrdinal()];

        // Δθemb - deltaTemp za izolacijo (polje R206)
        $deltaT_emb = self::DELTAT_VRSTE_SISTEMOV[$this->sistemOgreval->getOrdinal()] +
            self::DELTAT_SPECIFICNIH_IZGUB[$this->izolacija->getOrdinal()];

        // Δθstr - deltaTemp Str (polje Q208)
        $deltaT_str = self::DELTAT_VRSTE_SISTEMOV[$this->sistemOgreval->getOrdinal()];

        $deltaT = $deltaT_hydr + $deltaT_ctr + $deltaT_emb + $deltaT_str;

        $toplotneIzgube = [];
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $faktorDeltaT = $deltaT / ($cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec]);
            $toplotneIzgube[$mesec] = $cona->energijaOgrevanje[$mesec] * $faktorDeltaT;
        }

        return $toplotneIzgube;
    }
}