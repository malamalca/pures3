<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire\VrstaHladilnegaKompresorja;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire\VrstaHlajenjaHladilnegaSistema;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire\VrstaRegulacijeHladilnegaKompresorja;
use App\Calc\GF\TSS\TSSPorociloNiz;
use App\Calc\GF\TSS\TSSPorociloPodatek;
use App\Lib\Calc;

class HladilniKompresor extends Generator
{
    public float $nazivnaMoc;
    public float $EER;

    public float $mocRegulatorja;
    public float $steviloRegulatorjev;

    public bool $kondenzatorVKanalu = true;

    public VrstaHlajenjaHladilnegaSistema $vrstaHlajenja;
    public VrstaHladilnegaKompresorja $vrstaKompresorja;
    public VrstaRegulacijeHladilnegaKompresorja $vrstaRegulacije;

    public int $TizhodnegaZraka = 20;

    public array $korekcijskiFaktorEER = [0, 0, 0, 0, 0, 0, 0, 0, 0,0, 0, 0];
    public array $stUrDelovanjaNaDan = [0, 0, 0, 0, 0, 0, 0, 0, 0,0, 0, 0];

    private array $performanceLevels = [
        // VrstaHladilnegaKompresorja::Batni
        0 => [
            // VrstaRegulacijeHladilnegaKompresorja::OnOff
            0 => [0.83, 0.87, 0.92, 0.95, 0.98, 1.00, 1.01, 1.02, 1.01, 1.00],
            // VrstaRegulacijeHladilnegaKompresorja::Vecstopenjsko
            2 => [0.87, 1.03, 1.05, 1.06, 1.03, 1.08, 1.09, 1.07, 1.03, 1.00],
        ],
        // VrstaHladilnegaKompresorja::Spiralni
        1 => [
            // VrstaRegulacijeHladilnegaKompresorja::OnOff
            0 => [0.83, 0.87, 0.92, 0.95, 0.98, 1.00, 1.01, 1.02, 1.01, 1.00],
            // VrstaRegulacijeHladilnegaKompresorja::Vecstopenjsko
            2 => [0.87, 1.03, 1.05, 1.06, 1.03, 1.08, 1.09, 1.07, 1.03, 1.00],
            // VrstaRegulacijeHladilnegaKompresorja::Prilagodljivo
            1 => [0.43, 0.54, 0.65, 0.75, 0.84, 0.91, 0.97, 1.01, 1.02, 1.00],
        ],
        // VrstaHladilnegaKompresorja::Vijačni
        2 => [
            // VrstaRegulacijeHladilnegaKompresorja::Prilagodljivo
            1 => [1.19, 1.19, 1.13, 1.08, 1.05, 1.04, 1.03, 1.03, 1.02, 1.00],
        ],
        // VrstaHladilnegaKompresorja::Turbinski
        3 => [
            // VrstaRegulacijeHladilnegaKompresorja::Prilagodljivo
            1 => [1.4, 1.4, 1.32, 1.24, 1.18, 1.13, 1.09, 1.06, 1.03, 1.00],
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

        $this->vrstaHlajenja = VrstaHlajenjaHladilnegaSistema::from($config->vrstaHlajenja ?? 'zracno');
        $this->vrstaKompresorja = VrstaHladilnegaKompresorja::from($config->vrstaKompresorja ?? 'batni');
        $this->vrstaRegulacije = VrstaRegulacijeHladilnegaKompresorja::from($config->vrstaRegulacije ?? 'onOff');

        $this->TizhodnegaZraka = $config->TizhodnegaZraka ?? 20;

        $this->kondenzatorVKanalu = (bool)($config->kondenzatorVKanalu ?? false);

        $this->mocRegulatorja = $config->mocRegulatorja ?? 0;
        $this->steviloRegulatorjev = $config->steviloRegulatorjev ?? 1;
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

                $temperaturaOkoljaKondenzatorja =
                $this->kondenzatorVKanalu ? $cona->notranjaTHlajenje : $okolje->zunanjaT[$mesec];

                $idx = (int)(($f_Cpl > 1 ? 1 : round($f_Cpl, 1)) * 10 - 1);

                $vrstaKompresorja = $this->vrstaKompresorja->getOrdinal();

                if (!isset($this->performanceLevels[$vrstaKompresorja][$this->vrstaRegulacije->getOrdinal()][$idx])) {
                    throw new \Exception('Kombinacija kompresor/regulacija ne obstaja.');
                }
                $f_C_pl_k = $this->performanceLevels[$vrstaKompresorja][$this->vrstaRegulacije->getOrdinal()][$idx];

                $a = $this->vrstaKompresorja->faktorA($this->vrstaHlajenja);

                $f_hr_pl =
                    $a[0] + $a[1] * $temperaturaOkoljaKondenzatorja + $a[2] * pow($temperaturaOkoljaKondenzatorja, 2);

                $PLV = $f_C_pl_k * $f_hr_pl * 1 * 1;

                // f_EER_corr
                $this->korekcijskiFaktorEER[$mesec] =
                    (273.15 + $this->TizhodnegaZraka - $this->vrstaHlajenja->deltaTnaUparjalniku()) /
                        (
                            (
                                273.15 +
                                $temperaturaOkoljaKondenzatorja +
                                $this->vrstaHlajenja->deltaTnaKondenzatorju($this->kondenzatorVKanalu)
                            ) -
                            (273.15 + $this->TizhodnegaZraka - $this->vrstaHlajenja->deltaTnaUparjalniku())
                        )
                    /
                    (
                        (
                            273.15 +
                            $this->vrstaHlajenja->TnaIzstopuIzUparjalnika() -
                            $this->vrstaHlajenja->deltaTnaUparjalniku()
                        ) /
                        (
                            (
                                273.15 +
                                $this->vrstaHlajenja->TzaHlajenjeKondenzatorja() +
                                $this->vrstaHlajenja->deltaTnaKondenzatorju($this->kondenzatorVKanalu)
                            ) -
                            (
                                273.15 +
                                $this->vrstaHlajenja->TnaIzstopuIzUparjalnika() -
                                $this->vrstaHlajenja->deltaTnaUparjalniku()
                            )
                        )
                    );

                $E_C_gen_el_in = $vneseneIzgube[$mesec] /
                    ($this->korekcijskiFaktorEER[$mesec] * $this->EER * $PLV);
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
        if (!empty($this->mocRegulatorja)) {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
                $stUr = 24 * $stDni;
                $potrebnaEnergija = $cona->energijaHlajenje[$mesec] + $cona->energijaRazvlazevanje[$mesec];
                $this->stUrDelovanjaNaDan[$mesec] = ceil($potrebnaEnergija / $stDni / $this->nazivnaMoc);

                if ($vneseneIzgube[$mesec] > 0) {
                    $this->potrebnaElektricnaEnergija[$mesec] =
                        $this->stUrDelovanjaNaDan[$mesec] * $stDni *
                        $this->steviloRegulatorjev * $this->mocRegulatorja * 0.001;
                } else {
                    $this->potrebnaElektricnaEnergija[$mesec] = 0;
                }
            }
        } else {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $this->potrebnaElektricnaEnergija[$mesec] = 0;
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
        $this->porociloPodatki[] = new TSSPorociloPodatek(
            'EER<sub>n</sub>',
            'Nazivni faktor energijske učinkovitosti',
            $this->EER,
            'kW/kW'
        );
        $this->porociloPodatki[] = new TSSPorociloPodatek(
            '',
            'Vrsta regulacije',
            $this->vrstaRegulacije->getOrdinal() == 0 ? 'Inverter' : 'On/Off',
            ''
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
