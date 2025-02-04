<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Izbire\VrstaRegulacijeTemperature;
use App\Calc\GF\TSS\TSSInterface;
use App\Lib\Calc;

abstract class KoncniPrenosnik extends TSSInterface
{
    public string $vrsta = 'Končni prenosnik';
    public string $namen = 'ogrevanje';

    /**
     * Δθhydr - deltaTemp za hidravlično uravnoteženje sistema; prvi stolpec za stOgreval <= 10, drugi za > 10
     * temperature variation based on not balanced hydraulic systems (K)
     *
     * @var float $deltaT_hydr Δθhydr
     */
    public float $deltaT_hydr;

    /**
     * Δθctr - deltaTemp za regulacijo temperature; prvi stolpec sevala, drugi stolpec toplovod, h<4m
     * temperature variation based on control variation
     *
     * @var float $deltaT_ctr Δθctr
     */
    public float $deltaT_ctr;

    /**
     * Δθstr - deltaTemp Str (polje Q208)
     * spatial variation of temperature due to stratification (K)
     * glede na vrsto končnega prenosnika
     * - Ventilatorski konvektor - stenski -0,4
     * - Ventilatorski konvektor - stropni  0,0
     * - Podno hlajenje -0,7
     * - Stensko hlajenje -0,4
     * - Stropno hlajenje 0
     */
    public float $deltaT_str;

    /**
     * Δθemb - deltaTemp za izolacijo (polje R206)
     * temperature variation based on an additional heat loss of embedded emitters (K)
     * glede na vrsto končnega prenostnika
     * - Ventilatorski konvektor - stenski  0,0
     * - Ventilatorski konvektor - stropni  0,0
     * - Podno hlajenje -0,7
     * - Stensko hlajenje -0,4
     * - Stropno hlajenje -0,2
     */
    public float $deltaT_emb;

    /**
     * Δθim
     * temperature variation based on intermittent operation and based on the type of the emission system
     */
    public float $deltaT_im;

    /**
     * Δθe,sol
     * Temperature variation for consideration of solar and internal gains
     * - average proportion of window area or internal loads (e.g. residential buildings) Δθe,sol = 8 K
     * - high proportion of window area or internal loads (e.g. office buildings) Δθe,sol = 12 K
     */
    public float $deltaT_sol;

    /**
     * Moč pomožnih sistemov (črpake, ventilatorji,..)
     *
     * @var float $mocAux
     */
    public float $mocAux = 0.0;

    // za ogrevala
    public float $exponentOgrevala;

    // ΔpFBH – dodatek pri ploskovnem ogrevanju, če ni proizvajalčevega podatka je 25 kPa vključno z ventili in razvodom (kPa)
    public float $deltaP_FBH = 1;

    /**
     * @var int $stevilo
     */
    public int $stevilo = 1;

    /**
     * @var int $steviloRegulatorjev
     */
    public int $steviloRegulatorjev = 0;

    /**
     * @var float $mocRegulatorja
     */
    public float $mocRegulatorja = 0;

    public VrstaRegulacijeTemperature $regulacijaTemperature;

    /**
     * Class Constructor
     *
     * @param \stdClass|null $config Configuration
     * @return void
     */
    public function __construct(?\stdClass $config = null)
    {
        $this->id = $config->id;

        $this->stevilo = $config->stevilo ?? 1;
        $this->steviloRegulatorjev = $config->steviloRegulatorjev ?? 0;
        $this->mocRegulatorja = $config->mocRegulatorja ?? 0;

        $this->regulacijaTemperature = VrstaRegulacijeTemperature::from($config->regulacijaTemperature ?? 'centralna');

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // VENTILATORJI, ČRPALKE,...
        if (!empty($config->mocAux)) {
            $this->mocAux = $config->mocAux;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // VREDNOSTI deltaT glede na podizbire
        $this->deltaT_ctr = $this->regulacijaTemperature->deltaTCtr($this);
    }

    /**
     * Analiza podsistema
     *
     * @param array $toplotneIzgube Toplotne izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function analiza($toplotneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $this->toplotneIzgube($toplotneIzgube, $sistem, $cona, $okolje, $params);
        $this->potrebnaElektricnaEnergija($toplotneIzgube, $sistem, $cona, $okolje, $params);
        $this->vracljiveIzgubeAux($toplotneIzgube, $sistem, $cona, $okolje, $params);
    }

    /**
     * Izračun toplotnih izgub
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki cone
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        $deltaT = array_sum(
            [$this->deltaT_hydr, $this->deltaT_ctr, $this->deltaT_emb, $this->deltaT_str, $this->deltaT_im]
        );

        $notranjaT = !empty($params['namen']) && $params['namen'] == 'hlajenje' ?
            $cona->notranjaTHlajenje : $cona->notranjaTOgrevanje;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            if (($notranjaT - $okolje->zunanjaT[$mesec] - $this->deltaT_sol) == 0.0) {
                $faktorDeltaT = 0.0;
            } else {
                $faktorDeltaT = $deltaT / ($notranjaT - $okolje->zunanjaT[$mesec] - $this->deltaT_sol);
            }

            $this->toplotneIzgube[$namen][$mesec] = $vneseneIzgube[$mesec] * $faktorDeltaT;
        }

        return $this->toplotneIzgube;
    }

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function potrebnaElektricnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUrNaMesec = $stDni * 24;

            $this->potrebnaElektricnaEnergija[$namen][$mesec] =
                ($this->steviloRegulatorjev * $this->mocRegulatorja + $this->mocAux) *
                $sistem->steviloUrDelovanja($mesec, $cona, $okolje) / 1000;
        }

        return $this->potrebnaElektricnaEnergija;
    }

    /**
     * Uporabljena obnovljiva energija iz okolja
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function vracljiveIzgubeAux($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        //if (empty($this->potrebnaElektricnaEnergija)) {
        //    $this->potrebnaElektricnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params);
        //}

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->vracljiveIzgubeAux[$namen][$mesec] = $this->potrebnaElektricnaEnergija[$namen][$mesec];
        }

        return $this->vracljiveIzgubeAux;
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $sistem = parent::export();
        $sistem->vrsta = $this->vrsta;
        $sistem->regulacijaTemperature = $this->regulacijaTemperature->toString();

        return $sistem;
    }
}
