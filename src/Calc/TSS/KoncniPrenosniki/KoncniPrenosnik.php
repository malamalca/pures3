<?php

namespace App\Calc\TSS\KoncniPrenosniki;

use App\Calc\TSS\OgrevalniSistemi\ToplovodniOgrevalniSistem;

enum VrstaHidravlicnegaUravnotezenja: string {
    use \App\Lib\Traits\GetOrdinalTrait;

    case Neuravnotezeno = 'neuravnotezeno';
    case StaticnoUravnotezenjeKoncnihPrenosnikov = 'staticnoKoncnihPrenosnikov';
    case StaticnoUravnotezenjeDviznihVodov = 'staticnoDviznihVodov';
    case DinamicnoUravnotezenjePolnaObremenitev = 'dinamicnoPolnaObremenitev';
    case DinamicnoUravnotezenjeDelnaObremenitev = 'dinamicnoDelnaObremenitev';
}

enum VrstaRegulacijeTemperature: string {
    use \App\Lib\Traits\GetOrdinalTrait;

    case CentralnaRegulacija = 'centralna';
    case ReferencniProstor = 'referencniProstor';
    case P_krmilnik = 'P-krmilnik';
    case PI_krmilnik = 'PI-krmilnik';
    case PI_krmilnikZOptimizacijo = 'PI-krmilnikZOptimizacijo';
}

enum VrsteRezimov: string {
    use \App\Lib\Traits\GetOrdinalTrait;

    case Rezim_35_30 = '35/30';
    case Rezim_40_30 = '40/30';
    case Rezim_55_45 = '55/45';

    public function srednjaTemperatura($sistem)
    {
        if ($sistem instanceof ToplovodniOgrevalniSistem || $sistem->energent == 'elektrika') {
            // za ploskovno | toplovodno+elektrika
            $srednjeTemperature = [32.5, 35, 50];
        } else {
            // vse ostalo
            $srednjeTemperature = [35, 50, 62.5];
        }

        return $srednjeTemperature[$this->getOrdinal()];
    }

    // ΔθHK – temperaturna razlika pri standardnem temperaturnem režimu ogrevalnega sistema [°C]
    public function temperaturnaRazlikaStandardnegaRezima($sistem)
    {
        $deltaTHKLookup = [[5, 10], [10, 15], [10, 15]];

        // TODO:
        if ($sistem instanceof ToplovodniOgrevalniSistem/* || $sistem->energent == 'elektrika'*/) {
            $deltaT_HK = $deltaTHKLookup[$this->getOrdinal()][0];
        } else {
            $deltaT_HK = $deltaTHKLookup[$this->getOrdinal()][1];
        }
    }
}

abstract class KoncniPrenosnik {
    const DELTAT_REGULACIJE_TEMPERATURE = [2.5, 1.6, 0.7, 0.7, 0.5];
    const DELTAT_HIDRAVLICNEGA_URAVNOTEZENJA_DO_10 = [0.6, 0.3, 0.2, 0.1, 0];
    const DELTAT_HIDRAVLICNEGA_URAVNOTEZENJA_NAD_10 = [0.6, 0.4, 0.3, 0.2, 0];

    public $exponentOgrevala = 1;
    // ΔpFBH – dodatek pri ploskovnem ogrevanju, če ni proizvajalčevega podatka je 25 kPa vključno z ventili in razvodom (kPa)
    public $deltaP_FBH = 25;

    /**
     * @var int $steviloOgreval
     */
    protected int $steviloOgreval = 1;

    protected int $steviloRegulatorjev = 0;

    protected float $mocRegulatorja = 0;

    public VrstaHidravlicnegaUravnotezenja $hidravlicnoUravnotezenje;
    public VrstaRegulacijeTemperature $regulacijaTemperature;
    public VrsteRezimov $rezim;

    public function __construct($config = null)
    {
        if ($config) {
            $this->parseConfig($config);
        }
    }

    public function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->id = $config->id;

        $this->steviloOgreval = $config->steviloOgreval ?? 1;
        $this->steviloRegulatorjev = $config->steviloRegulatorjev ?? 0;
        $this->mocRegulatorja = $config->mocRegulatorja ?? 0;

        $this->hidravlicnoUravnotezenje = VrstaHidravlicnegaUravnotezenja::from($config->hidravlicnoUravnotezenje ?? 'neuravnotezeno');
        $this->regulacijaTemperature = VrstaRegulacijeTemperature::from($config->regulacijaTemperature ?? 'centralna');
        $this->rezim = VrsteRezimov::from($config->rezim);
    }

    abstract function toplotneIzgube($cona, $okolje);
    
    public function elektricneIzgube($cona, $okolje) {
        $elektricneIzgube = [];
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = $stDni * 24;

            $elektricneIzgube[$mesec] = $stUr * $this->steviloRegulatorjev * $this->mocRegulatorja;
        }
    }
    
    public function vrnjenaToplota($cona, $okolje) {
        return $this->elektricneIzgube($cona, $okolje);
    }
}