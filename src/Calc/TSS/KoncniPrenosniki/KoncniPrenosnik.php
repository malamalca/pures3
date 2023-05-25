<?php
declare(strict_types=1);

namespace App\Calc\TSS\KoncniPrenosniki;

use App\Calc\TSS\KoncniPrenosniki\Izbire\VrstaHidravlicnegaUravnotezenja;
use App\Calc\TSS\KoncniPrenosniki\Izbire\VrstaRegulacijeTemperature;
use App\Lib\Calc;

abstract class KoncniPrenosnik
{
    public const DELTAT_REGULACIJE_TEMPERATURE = [2.5, 1.6, 0.7, 0.7, 0.5];
    public const DELTAT_HIDRAVLICNEGA_URAVNOTEZENJA_DO_10 = [0.6, 0.3, 0.2, 0.1, 0];
    public const DELTAT_HIDRAVLICNEGA_URAVNOTEZENJA_NAD_10 = [0.6, 0.4, 0.3, 0.2, 0];

    public string $id;

    public $exponentOgrevala;

    // ΔpFBH – dodatek pri ploskovnem ogrevanju, če ni proizvajalčevega podatka je 25 kPa vključno z ventili in razvodom (kPa)
    public $deltaP_FBH = 25;

    /**
     * @var int $steviloOgreval
     */
    protected int $steviloOgreval = 1;

    /**
     * @var int $steviloRegulatorjev
     */
    protected int $steviloRegulatorjev = 0;

    /**
     * @var float $mocRegulatorja
     */
    protected float $mocRegulatorja = 0;

    public VrstaHidravlicnegaUravnotezenja $hidravlicnoUravnotezenje;
    public VrstaRegulacijeTemperature $regulacijaTemperature;

    public array $toplotneIzgube;

    /**
     * Class Constructor
     *
     * @param \StdClass|string|null $config Configuration
     * @return void
     */
    public function __construct($config = null)
    {
        if ($config) {
            $this->parseConfig($config);
        }
    }

    /**
     * Loads configuration from json|StdClass
     *
     * @param string|\StdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->id = $config->id;

        $this->steviloOgreval = $config->steviloOgreval ?? 1;
        $this->steviloRegulatorjev = $config->steviloRegulatorjev ?? 0;
        $this->mocRegulatorja = $config->mocRegulatorja ?? 0;

        $this->hidravlicnoUravnotezenje =
            VrstaHidravlicnegaUravnotezenja::from($config->hidravlicnoUravnotezenje ?? 'neuravnotezeno');
        $this->regulacijaTemperature = VrstaRegulacijeTemperature::from($config->regulacijaTemperature ?? 'centralna');
    }

    /**
     * Izračun toplotnih izgub
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \StdClass $cona Podatki cone
     * @param \StdClass $okolje Podatki cone
     * @return array
     */
    abstract public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje);

    /**
     * Izračun potrebne električne energije
     *
     * @param \StdClass $cona Podatki cone
     * @param \StdClass $okolje Podatki cone
     * @return array
     */
    public function potrebnaElektricnaEnergija($cona, $okolje)
    {
        $elektricneIzgube = [];
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = $stDni * 24;

            $elektricneIzgube[$mesec] = $stUr * $this->steviloRegulatorjev * $this->mocRegulatorja;
        }

        return $elektricneIzgube;
    }

    /**
     * Izračun v sistem vrnjene toplote
     *
     * @param \StdClass $cona Podatki cone
     * @param \StdClass $okolje Podatki cone
     * @return array
     */
    public function vrnjenaToplota($cona, $okolje)
    {
        return $this->potrebnaElektricnaEnergija($cona, $okolje);
    }
}
