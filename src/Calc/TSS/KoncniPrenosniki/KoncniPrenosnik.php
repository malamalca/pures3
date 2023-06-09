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

    public float $exponentOgrevala;

    // ΔpFBH – dodatek pri ploskovnem ogrevanju, če ni proizvajalčevega podatka je 25 kPa vključno z ventili in razvodom (kPa)
    public float $deltaP_FBH = 1;

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
    public array $potrebnaElektricnaEnergija;
    public array $vracljiveIzgubeAux;

    /**
     * Class Constructor
     *
     * @param \stdClass|null $config Configuration
     * @return void
     */
    public function __construct($config = null)
    {
        if ($config) {
            $this->parseConfig($config);
        }
    }

    /**
     * Loads configuration from json|stdClass
     *
     * @param null|\stdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        $this->id = $config->id;

        $this->steviloOgreval = $config->steviloOgreval ?? 1;
        $this->steviloRegulatorjev = $config->steviloRegulatorjev ?? 0;
        $this->mocRegulatorja = $config->mocRegulatorja ?? 0;

        $this->hidravlicnoUravnotezenje =
            VrstaHidravlicnegaUravnotezenja::from($config->hidravlicnoUravnotezenje ?? 'neuravnotezeno');
        $this->regulacijaTemperature = VrstaRegulacijeTemperature::from($config->regulacijaTemperature ?? 'centralna');
    }
    
    /**
     * Analiza podsistema
     *
     * @param array $potrebnaEnergija Potrebna energija predhodnih TSS
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function analiza($potrebnaEnergija, $sistem, $cona, $okolje, $params = [])
    {
        $this->toplotneIzgube($potrebnaEnergija, $sistem, $cona, $okolje, $params);
        $this->potrebnaElektricnaEnergija($potrebnaEnergija, $sistem, $cona, $okolje, $params);
    }

    /**
     * Izračun toplotnih izgub
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki cone
     * @return array
     */
    abstract public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje);

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function potrebnaElektricnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = $stDni * 24;

            // th – mesečne obratovalne ure – čas [h/M] (enačba 43)
            $steviloUr = $stUr * ($sistem->povprecnaObremenitev[$mesec] > 0.05 ?
                1 :
                $sistem->povprecnaObremenitev[$mesec] / 0.05);

            $this->potrebnaElektricnaEnergija[$mesec] =
                $steviloUr * $this->steviloRegulatorjev * $this->mocRegulatorja / 1000;
        }

        return $this->potrebnaElektricnaEnergija;
    }

    /**
     * Uporabljena obnovljiva energija iz okolja
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function vracljiveIzgubeAux($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        if (empty($this->potrebnaElektricnaEnergija)) {
            $this->potrebnaElektricnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = []);
        }

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->vracljiveIzgubeAux[$mesec] = $this->potrebnaElektricnaEnergija[$mesec];
        }

        return $this->vracljiveIzgubeAux;
    }
}
