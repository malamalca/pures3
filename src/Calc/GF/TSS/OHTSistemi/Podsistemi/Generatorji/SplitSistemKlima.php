<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji;

use App\Calc\GF\TSS\TSSPorociloNiz;
use App\Calc\GF\TSS\TSSPorociloPodatek;
use App\Lib\Calc;

class SplitSistemKlima extends Generator
{
    public float $nazivnaMoc;
    public float $EER;

    public int $TzaHlajenjeKondenzatorja = 35;
    public int $TnaIzstopuIzUparjalnika = 27;
    public int $deltaTnaKondenzatorju = 10;
    public int $deltaTnaUparjalniku = 20;

    public int $TizhodnegaZraka = 20;

    public array $korekcijskiFaktorEER = [0, 0, 0, 0, 0, 0, 0, 0, 0,0, 0, 0];
    public array $stUrDelovanjaNaDan = [0, 0, 0, 0, 0, 0, 0, 0, 0,0, 0, 0];

    /**
     * @var int $vrstaRegulacije 0-Inverter, 1-OnOff
     */
    public int $vrstaRegulacije = 0;

    /**
     * @var int $vrsta split sistema 0-split, 1-multiSplit
     */
    public int $vrstaIdx = 0;

    private array $faktorRac = [
        // split
        0 => [
            // za inverter
            0 => [1.52, 1.54, 1.57, 1.69, 1.45, 1.31, 1.21, 1.09, 1.03, 0.95],
            // za on/off
            1 => [1.34, 1.34, 1.34, 1.34, 1.27, 1.23, 1.16, 1.09, 1.02, 0.95],
        ],
        // multiSplit
        1 => [
            // za inverter
            0 => [0.77, 1.18, 1.42, 1.55, 1.54, 1.46, 1.35, 1.19, 1.06, 0.92],
            // za on/off
            1 => [0.68, 0.73, 0.77, 0.80, 0.86, 0.93, 0.95, 0.97, 0.94, 0.90],
        ],
    ];

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        parent::parseConfig($config);

        if (!isset($config->nazivnaMoc)) {
            throw new \Exception('Ni vpisane nazivne moči split sistema hlajenja.');
        }

        if (!isset($config->EER)) {
            throw new \Exception('Ni vpisanega EER faktorja split sistema hlajenja.');
        }

        $this->nazivnaMoc = $config->nazivnaMoc;
        $this->EER = $config->EER;

        $this->vrstaIdx = isset($config->multiSplit) && $config->multiSplit === true ? 1 : 0;
        $this->vrstaRegulacije = isset($config->regulacija) && strtolower($config->regulacija) == 'onoff' ? 1 : 0;

        $this->TizhodnegaZraka = $config->TizhodnegaZraka ?? 20;
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
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->vneseneIzgube['hlajenje'][$mesec] = $vneseneIzgube[$mesec];

            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = 24 * $stDni;

            $potrebnaEnergija = $cona->energijaHlajenje[$mesec] + $cona->energijaRazvlazevanje[$mesec];

            $this->stUrDelovanjaNaDan[$mesec] = ceil($potrebnaEnergija / $stDni / $this->nazivnaMoc);

            // t_C_gen_op_m
            $stUrNaMesec = $this->stUrDelovanjaNaDan[$mesec] * $stDni;

            if ($stUrNaMesec > 0) {
                // f_C,pl
                $f_Cpl = $potrebnaEnergija / $stUrNaMesec / $this->nazivnaMoc;

                $idx = (int)(($f_Cpl > 1 ? 1 : round($f_Cpl, 1)) * 10 - 1);
                $f_C_pl_k = $this->faktorRac[$this->vrstaIdx][$this->vrstaRegulacije][$idx];

                //$a0 = 1;
                //$a1 = 0;
                //$a2 = 0;
                //$f_hr_pl = $a0 + $a1 * $TizhodnegaZraka + $a2 * pow($TizhodnegaZraka, 2);
                //$PLV = $f_C_pl_k * $f_hr_pl * 1 * 1;

                // f_EER_corr
                $this->korekcijskiFaktorEER[$mesec] =
                    (273.15 + $this->TizhodnegaZraka - $this->deltaTnaUparjalniku) /
                        (
                            (273.15 + $okolje->zunanjaT[$mesec] + $this->deltaTnaKondenzatorju) -
                            (273.15 + $this->TizhodnegaZraka - $this->deltaTnaUparjalniku)
                        )
                    /
                    (
                        (273.15 + $this->TnaIzstopuIzUparjalnika - $this->deltaTnaUparjalniku) /
                        (
                            (273.15 + $this->TzaHlajenjeKondenzatorja + $this->deltaTnaKondenzatorju) -
                            (273.15 + $this->TnaIzstopuIzUparjalnika - $this->deltaTnaUparjalniku)
                        )
                    );

                $E_C_gen_el_in =
                    ($cona->energijaHlajenje[$mesec] + $cona->energijaRazvlazevanje[$mesec]) /
                    ($this->korekcijskiFaktorEER[$mesec] * $this->EER * $f_C_pl_k);
            } else {
                $this->korekcijskiFaktorEER[$mesec] = 0;
                $E_C_gen_el_in = 0;
            }

            // potrebna energija za hlajenje
            $this->toplotneIzgube['hlajenje'][$mesec] = $E_C_gen_el_in;
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
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->potrebnaElektricnaEnergija['hlajenje'][$mesec] = 0;
            $this->vracljiveIzgubeAux[$mesec] = 0;
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
        $this->porociloPodatki[] = new TSSPorociloPodatek(
            'EER<sub>n</sub>',
            'Nazivni faktor energijske učinkovitosti',
            $this->EER,
            'kW/kW'
        );
        $this->porociloPodatki[] = new TSSPorociloPodatek(
            '',
            'Vrsta hladilne naprave',
            $this->vrstaIdx == 0 ? 'Split' : 'MultiSplit',
            ''
        );
        $this->porociloPodatki[] = new TSSPorociloPodatek(
            '',
            'Vrsta regulacije',
            $this->vrstaRegulacije == 0 ? 'Inverter' : 'On/Off',
            ''
        );

        $this->porociloPodatki[] = new TSSPorociloPodatek(
            'ϑ<sub>C,gen,hr,req,in,n</sub>',
            'Nazivna temperatura vode ali zraka za hlajenje kondezatorja',
            $this->TzaHlajenjeKondenzatorja,
            '°C'
        );

        $this->porociloPodatki[] = new TSSPorociloPodatek(
            'ϑ<sub>C,gen,req,out,n</sub>',
            'Nazivna temperatura nosilca hladu na izstopu iz uparjalnika',
            $this->TnaIzstopuIzUparjalnika,
            '°C'
        );

        $this->porociloPodatki[] = new TSSPorociloPodatek(
            'Δϑ<sub>cond</sub>',
            'Temperaturna razlika na kondenzatorju',
            $this->deltaTnaKondenzatorju,
            'K'
        );

        $this->porociloPodatki[] = new TSSPorociloPodatek(
            'Δϑ<sub>evap</sub>',
            'Temperaturna razlika na uparjalniku',
            $this->deltaTnaUparjalniku,
            'K'
        );

        $this->porociloPodatki[] = new TSSPorociloPodatek(
            'ϑ<sub>C,gen,req,out</sub>',
            'Temperatura vpihovanega zraka ali ohlajene vode',
            $this->TizhodnegaZraka,
            '°C'
        );

        $this->porociloNizi[] = new TSSPorociloNiz(
            't<sub>C,gen,op,d</sub>',
            'Število ur delovanja na dan',
            $this->stUrDelovanjaNaDan,
            1
        );

        $this->porociloNizi[] = new TSSPorociloNiz(
            'f<sub>EER,corr</sub>',
            'Korekcijski faktor ERR',
            $this->korekcijskiFaktorEER,
            2,
            false
        );

        $sistem = parent::export();

        $sistem->nazivnaMoc = $this->nazivnaMoc;

        return $sistem;
    }
}
