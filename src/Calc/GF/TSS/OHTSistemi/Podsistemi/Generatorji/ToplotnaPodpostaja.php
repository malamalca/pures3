<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire\TipToplotnePodpostaje;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire\VrstaLokacijeNamestitve;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire\VrstaRazredaIzolacije;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire\VrstaRegulacijeKotla;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Hranilniki\PosrednoOgrevanHranilnik;
use App\Lib\Calc;

class ToplotnaPodpostaja extends Generator
{
    public TipToplotnePodpostaje $tip;
    public VrstaLokacijeNamestitve $lokacija;
    public VrstaRazredaIzolacije $razredIzolacije;
    public VrstaRegulacijeKotla $regulacija;
    public bool $znotrajOvoja = true;

    /**
     * Class Constructor
     *
     * @param \stdClass $config Configuration
     * @return void
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
    }

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        parent::parseConfig($config);

        /** @var \stdClass $config */
        $this->tip = TipToplotnePodpostaje::from($config->tip);
        $this->razredIzolacije = VrstaRazredaIzolacije::from($config->razredIzolacije);
        $this->regulacija = VrstaRegulacijeKotla::from($config->regulacija);
        $this->lokacija = VrstaLokacijeNamestitve::from($config->lokacija ?? 'ogrevanProstor');
    }

    /**
     * Izračun potrebne energije generatorja
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        switch ($namen) {
            case 'ogrevanje':
                $this->toplotneIzgubeOgrevanje($vneseneIzgube, $sistem, $cona, $okolje, $params);
                break;
            case 'tsv':
                $this->toplotneIzgubeTSV($vneseneIzgube, $sistem, $cona, $okolje, $params);
                break;
        }
    }

    /**
     * Izračun potrebne energije generatorja v sistemu ST
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    private function toplotneIzgubeTSV($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $rezimRazvoda = $sistem->tsv->rezim;

        $temperaturaOkolice =
            $this->lokacija == VrstaLokacijeNamestitve::OgrevanProstor ? $cona->notranjaTOgrevanje : 13;

        // T_h_g - obratovalna temperatura generatorja toplote
        $T_h_g = 45;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = $stDni * 24;

            // faktor [-]
            // todo: v pdf je exponent 1,3, ne 1/3
            // enačba 400
            $H_ds = $this->tip->faktorBds($this->razredIzolacije) * pow($this->nazivnaMoc, 1 / 3);

            // T_DS povprečna temperatura ogrevalnega medija sistema daljinskega ogrevanja [°C]
            // enačba 401
            $temperaturaMedija = $this->tip->faktorDds() * $this->tip->temperaturaPrimarnegaMedija() +
                (1 - $this->tip->faktorDds()) * 50;

            // Toplotne izgube toplotne podpostaje
            // enačba 399
            $Q_w_DO_l = $H_ds * ($temperaturaMedija - $temperaturaOkolice) * $stDni / 365;

            ////////////////////////////////////////////////////////////////////////////////////////////////////////////
            $this->vneseneIzgube['tsv'][$mesec] = $vneseneIzgube[$mesec];
            $this->toplotneIzgube['tsv'][$mesec] = $Q_w_DO_l;

            $this->vracljiveIzgube[$mesec] = 0;
        }
    }

    /**
     * Izračun potrebne energije generatorja v sistemu ogrevanja
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    private function toplotneIzgubeOgrevanje($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'] ?? 'ogrevanje';
        $rezimRazvoda = $sistem->{$namen}->rezim;

        $temperaturaOkolice =
            $this->lokacija == VrstaLokacijeNamestitve::OgrevanProstor ? $cona->notranjaTOgrevanje : 13;

        // T_h_g - obratovalna temperatura generatorja toplote
        $T_h_g = 45;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = $stDni * 24;

            // th – mesečne obratovalne ure – čas [h/M]
            // enačba 43
            $stUrOgrevanje = $sistem->steviloUrDelovanja($mesec, $cona, $okolje);

            $Fc = $this->regulacija->faktorRegulacije($mesec, $cona, $okolje);

            // povprečna temperatura ogreval [°C]
            $T_h_em = $rezimRazvoda->projektnaTemperatura() +
                $Fc * ($cona->notranjaTOgrevanje - $rezimRazvoda->projektnaTemperatura());

            // faktor [-]
            // todo: v pdf je exponent 1,3, ne 1/3
            // enačba 400
            $H_ds = $this->tip->faktorBds($this->razredIzolacije) * pow($this->nazivnaMoc, 1 / 3);

            // T_DS povprečna temperatura ogrevalnega medija sistema daljinskega ogrevanja [°C]
            // enačba 401
            $temperaturaMedija = $this->tip->faktorDds() * $this->tip->temperaturaPrimarnegaMedija() +
                (1 - $this->tip->faktorDds()) * max($T_h_g, $T_h_em);

            // Toplotne izgube toplotne podpostaje
            // enačba 399
            $Q_h_DO_l = $H_ds * ($temperaturaMedija - $temperaturaOkolice) * $stUrOgrevanje / 24 / 365;

            ////////////////////////////////////////////////////////////////////////////////////////////////////////////
            $this->vneseneIzgube['ogrevanje'][$mesec] = $vneseneIzgube[$mesec];
            $this->toplotneIzgube['ogrevanje'][$mesec] = $Q_h_DO_l;
            $this->vracljiveIzgube[$mesec] = $Q_h_DO_l;
        }
    }

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function potrebnaElektricnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        if ($namen == 'tsv') {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
                $stUr = $stDni * 24;
                $stDniTSV = $stDni;

                if (!empty($sistem->hranilniki)) {
                    foreach ($sistem->hranilniki as $hranilnik) {
                        if ($hranilnik instanceof PosrednoOgrevanHranilnik) {
                            // POLNJENJE POSREDNO OGREVANEGA HRANILNIKA
                            // t - čas delovanja črpalke [h]
                            // enačba 153
                            $t_p = $vneseneIzgube[$mesec] * 1.1 / $this->nazivnaMoc;

                            // nazivna moč črpalke [W]. Podatek proizvajalca ali prevzeta vrednost
                            // todo: to lahko uporabnik določi kot podatek v $config
                            // todo: ta del se prestavi v PosrednoOgrevanHranilnik
                            // enačba 152
                            $P_p = 44 + 0.005 * pow($hranilnik->volumen, 1.43);

                            // potrebna električna energija za pogon črpalke [kWh]
                            // enačba 151
                            $W_w_s_aux = 0.001 * $P_p * $t_p;

                            // Delež vrnjene energije v ogrevni medij
                            // enačba 155
                            $Q_w_rww_s = $W_w_s_aux * 0.25;

                            // Delež vrnjene energije v okoliški zrak, če je črpalka nameščena v ogrevanem prostoru (coni):
                            // enačba 156
                            $Q_w_rwh_s = $W_w_s_aux * 0.25 * ($hranilnik->znotrajOvoja ? 1 : 0);

                            ////////////////////////////////////////////////////////////////////////////////////////////
                            $this->potrebnaElektricnaEnergija['tsv'][$mesec] = $W_w_s_aux;
                            $this->vracljiveIzgubeAux[$mesec] = ($this->vracljiveIzgubeAux[$mesec] ?? 0) + $Q_w_rwh_s;
                            $this->vracljiveIzgubeTSV[$mesec] = ($this->vracljiveIzgubeTSV[$mesec] ?? 0) + $Q_w_rww_s;
                        }
                    }
                }

                $W_w_g_aux = 10 * $stDni / $stDniTSV;

                ///////////////////////////////////////////////////////////////////////////////////////////////////////
                $this->potrebnaElektricnaEnergija['tsv'][$mesec] =
                    ($this->potrebnaElektricnaEnergija['tsv'][$mesec] ?? 0) + $W_w_g_aux;

                $this->vracljiveIzgubeAux[$mesec] = ($this->vracljiveIzgubeAux[$mesec] ?? 0) +
                    ($this->lokacija == VrstaLokacijeNamestitve::OgrevanProstor ? $W_w_g_aux : 0);
            }
        } else {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
                $stUr = $stDni * 24;

                // th – mesečne obratovalne ure – čas [h/M]
                // enačba 43
                $stUrOgrevanje = $sistem->steviloUrDelovanja($mesec, $cona, $okolje);

                $this->potrebnaElektricnaEnergija['ogrevanje'][$mesec] = 10 * $stUrOgrevanje / 24 / $stDni;

                $this->vracljiveIzgubeAux[$mesec] = $this->lokacija == VrstaLokacijeNamestitve::OgrevanProstor ?
                    $this->potrebnaElektricnaEnergija['ogrevanje'][$mesec] * 0.6 :
                    $this->potrebnaElektricnaEnergija['ogrevanje'][$mesec] * (1 - 0.3) * 0.6;
            }
        }
    }

    /**
     * Uporabljena obnovljiva energija iz okolja
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function obnovljivaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->obnovljivaEnergija[$namen][$mesec] = 0;
        }
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $sistem = parent::export();
        $sistem->lokacija = $this->lokacija->value;
        $sistem->nazivnaMoc = $this->nazivnaMoc;

        return $sistem;
    }
}
