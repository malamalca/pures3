<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji;

use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Izbire\VrstaLokacijeNamestitve;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Izbire\VrstaRegulacijeKotla;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Hranilniki\PosrednoOgrevanHranilnik;
use App\Calc\GF\TSS\TSSPorociloNiz;
use App\Calc\GF\TSS\TSSPorociloPodatek;
use App\Lib\Calc;

class Kotel extends Generator
{
    public mixed $tip;
    public VrstaLokacijeNamestitve $lokacija;
    public VrstaRegulacijeKotla $regulacija;
    public bool $znotrajOvoja = true;

    private array $beta_h_g;
    private string $tipKotlaClass;

    private array $porociloNizi = [];

    /**
     * Class Constructor
     *
     * @param string $tipKotla Tip kotla
     * @param \stdClass $config Configuration
     * @return void
     */
    public function __construct($tipKotla, $config = null)
    {
        $this->tipKotlaClass = $tipKotla;
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
        $tip = '\\App\\Calc\\GF\\TSS\\OgrevalniSistemi\\Podsistemi\\Generatorji\\Izbire\\Tip' . $this->tipKotlaClass;
        $this->tip = $tip::from($config->tip);
        $this->regulacija = VrstaRegulacijeKotla::from($config->regulacija);
        $this->lokacija = VrstaLokacijeNamestitve::from($config->lokacija ?? 'ogrevanProstor');
    }

    /**
     * Izračun potrebne energije generatorja
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
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
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    private function toplotneIzgubeTSV($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $izk100 = $this->tip->izkoristekPolneObremenitve($this->nazivnaMoc);
        $f_kor100 = $this->tip->korekcijskiFaktorIzkoristkaPolneObremenitve();

        $temperaturaOkolice =
            $this->lokacija == VrstaLokacijeNamestitve::OgrevanProstor ? $cona->notranjaTOgrevanje : 13;

        // srednja temperatura vode v kotlu [°C]. Za poenostavitev lahko prevzamemo za
        // sisteme z delujočo cirkulacijo v stanju obratovalne pripravljenosti 50°C, za
        // kombinirane kotle, obtočne grelnike in sisteme brez oziroma izklopljeno cirkulacijo 40°C.
        $temperaturaVode = 40;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = $stDni * 24;
            $stDniTSV = $stDni;
            $stUrNaDanTSV = 24;

            // izračunam čas, ki je potreben za pripravo TSV
            // todo: excel ne upošteva
            // enačba 152
            $stUrTSV = $vneseneIzgube[$mesec] / ($this->nazivnaMoc * 24);
            $stUrTSV = 0;

            // Mesečni računski obratovalni dnevi
            // enačba 46
            $d_h_rod = 0;

            // enačba 116
            $Q_w_out_g = $vneseneIzgube[$mesec] * $stDni / $stDniTSV;

            // dnevne toplotne izgube grelnika (kotla) pri obratovanju z nazivno močjo [kWh]
            // enačba 132
            $Q_w_g_l_100 = ($sistem->energent->maksimalniIzkoristek() - $izk100) / $izk100 * $Q_w_out_g / $stDniTSV;

            // enačba 131
            $Q_w_g_l = $Q_w_g_l_100 * $stDniTSV;

            $this->toplotneIzgube['tsv'][$mesec] = $Q_w_g_l;

            // Potrebna električna energija za delovanje kotla Ww,g,aux
            // enačba 158
            $t_w_100 = $vneseneIzgube[$mesec] / ($this->nazivnaMoc * $stDniTSV);

            // specifične toplotne izgube kotla pri srednji temperaturi vode v kotlu 70°C [-]
            // tabela 24
            $q_w_g_70 = $this->tip->izgube70($this->nazivnaMoc);

            // enačba 134
            $q_w_g_T = $q_w_g_70 * ($temperaturaVode - $temperaturaOkolice) / (70 - 20);

            $q_s = $this->tip->faktorIzgubSkoziOvoj() * $q_w_g_T;

            // iz excela
            // todo: nepoznana enačba
            $Q_w_g_rwg_env = $q_s * $this->nazivnaMoc / $izk100 *
                ($t_w_100 * $stDniTSV + ($stUrNaDanTSV - $t_w_100) * ($stDniTSV - $d_h_rod));

            $this->vracljiveIzgube[$mesec] = ($this->vracljiveIzgube[$mesec] ?? 0) + $Q_w_g_rwg_env;
        }
    }

    /**
     * Izračun potrebne energije generatorja v sistemu ogrevanja
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    private function toplotneIzgubeOgrevanje($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $rezimRazvoda = $params['rezim'];

        $izk100 = $this->tip->izkoristekPolneObremenitve($this->nazivnaMoc);
        $f_kor100 = $this->tip->korekcijskiFaktorIzkoristkaPolneObremenitve();

        $izk30 = $this->tip->izkoristekVmesneObremenitve($this->nazivnaMoc);
        $f_kor30 = $this->tip->korekcijskiFaktorIzkoristkaVmesneObremenitve();

        $temperaturaOkolice =
            $this->lokacija == VrstaLokacijeNamestitve::OgrevanProstor ? $cona->notranjaTOgrevanje : 13;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = $stDni * 24;

            // izračunam čas, ki je potreben za pripravo TSV
            // todo: Excel ne upošteva
            // enačba 152
            $stUrTSV = $cona->energijaTSV[$mesec] / ($this->nazivnaMoc * 24);
            $stUrTSV = 0;

            // th – mesečne obratovalne ure – čas [h/M]
            // enačba 43
            $stUrOgrevanje = $stUr * ($sistem->povprecnaObremenitev[$mesec] > 0.05 ?
                1 :
                $sistem->povprecnaObremenitev[$mesec] / 0.05);

            $Fc = $this->regulacija->faktorRegulacije($mesec, $cona, $okolje);

            // povprečna temperatura ogreval [°C]
            $T_h_em = $rezimRazvoda->projektnaTemperatura() +
                $Fc * ($cona->notranjaTOgrevanje - $rezimRazvoda->projektnaTemperatura());

            // obratovalna temperatura generatorja
            // enačba 83
            $T_h_g = max($T_h_em, $this->tip->temperaturnaOmejitev());

            // Izkoristek kotla pri 100% obremenitvi
            // enačba 90
            $ni_h_g_Pn_cor = $izk100 + $f_kor100 * ($this->tip->temperaturaKotlaPolneObremenitve() - $T_h_g);

            // toplotne izgube pri 100% obremenitvi
            // enačba 91
            $Q_h_g_Pn_cor = ($sistem->energent->maksimalniIzkoristek() - $ni_h_g_Pn_cor) / $ni_h_g_Pn_cor *
                $this->nazivnaMoc;

            // Izkoristek kotla pri delno (30%) obremenitvi
            // enačba 92
            $ni_h_g_Pint_cor = $izk30 + $f_kor30 * ($this->tip->temperaturaKotlaVmesneObremenitve() - $T_h_g);

            // toplotne izgube pri vmesni obremenitvi
            // enačba 93
            $Q_h_g_Pint_cor = ($sistem->energent->maksimalniIzkoristek() - $ni_h_g_Pint_cor) / $ni_h_g_Pint_cor *
                $this->nazivnaMoc;

            // skladno z opombo nad enačbo 93
            $Q_h_g_Pint_cor = $Q_h_g_Pint_cor * 0.3;

            // Toplotne izgube pri 0% obremenitvi so določene za temperaturno razliko 30 K. - enačba 94q
            // enačba 94
            $Q_h_g_P0_cor =
                $this->tip->izgubeStandBy($this->nazivnaMoc) * pow(($T_h_g - $temperaturaOkolice) / 30, 1.25);

            // povprečna toplotna moč oddana v razvodni ogrevalni podsistem [kW]
            // enačba 96
            $Q_h_in_d = $stUrOgrevanje == 0 ? 0 : $vneseneIzgube[$mesec] / $stUrOgrevanje;

            // Razmerje toplotne obremenitve posameznega (i-tega) generatorja toplote h, g,i β pri paralelni
            // priključitvi j generatorjev. Vsi generatorji delujejo istočasno: obremenitev posameznega
            // generatorja ustreza razmerju skupne povprečne toplotne obremenitve:
            // enačba 95
            $this->beta_h_g[$mesec] = $Q_h_in_d / $this->nazivnaMoc;

            // Obremenitev kotla pri testnih pogojih za vmesno obremenitev β_h, g, test, Pint
            // Za enačbo 98; Excel AN254
            $beta_h_g_test_Pint = $this->tip->vmesnaObremenitev();

            // Toplotne izgube generatorja toplote v odvisnosti od razmerja obremenitve
            if ($this->beta_h_g[$mesec] < $beta_h_g_test_Pint) {
                // enačba 98a
                $Q_h_g_l = $this->beta_h_g[$mesec] / $beta_h_g_test_Pint *
                    ($Q_h_g_Pint_cor - $Q_h_g_P0_cor) + $Q_h_g_P0_cor;
            } else {
                // enačba 98b
                $Q_h_g_l = ($this->beta_h_g[$mesec] - $beta_h_g_test_Pint) / (1 - $beta_h_g_test_Pint) *
                    ($Q_h_g_Pn_cor - $Q_h_g_Pint_cor) + $Q_h_g_Pint_cor;
            }

            // Skupne toplotne izgube v času opazovanega časovnega intervala
            // enačba 99
            $Qh_g_l = $Q_h_g_l * ($stUrOgrevanje - $stUrTSV);

            // Toplotne izgube skozi ovoj generatorja toplote
            // enačba 106
            $this->vracljiveIzgube[$mesec] = $Q_h_g_P0_cor *
                (1 - $this->tip->delezVrnjenihIzgubSkoziOvoj($this->lokacija)) *
                $this->tip->faktorIzgubSkoziOvoj() * $stUrOgrevanje;

            $this->toplotneIzgube['ogrevanje'][$mesec] = $Qh_g_l;
        }

        $this->porociloNizi['betaH'] = new TSSPorociloNiz(
            '&beta;<sub>H,gen</sub>',
            'Razmerje toplotne obremenitve posameznega (i-tega) generatorja toplote.',
            $this->beta_h_g,
            2
        );
    }

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function potrebnaElektricnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        if ($namen == 'tsv') {
            $this->vneseneIzgube['tsv'] = $vneseneIzgube;

            $mocPomoznih100 = $this->tip->mocPomoznihElektricnihNaprav($this->nazivnaMoc, 'polna');
            $mocPomoznih0 = $this->tip->mocPomoznihElektricnihNaprav($this->nazivnaMoc, 'min');
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

                            $this->potrebnaElektricnaEnergija['tsv'][$mesec] = $W_w_s_aux;

                            $this->vracljiveIzgubeAux[$mesec] = ($this->vracljiveIzgubeAux[$mesec] ?? 0) + $Q_w_rwh_s;

                            $this->vracljiveIzgubeTSV[$mesec] = ($this->vracljiveIzgubeTSV[$mesec] ?? 0) + $Q_w_rww_s;
                        }
                    }
                }

                // Potrebna električna energija za delovanje kotla Ww,g,aux
                // enačba 158
                $t_w_100 = $vneseneIzgube[$mesec] / ($this->nazivnaMoc * $stDniTSV);

                // Mesečni računski obratovalni dnevi
                // enačba 46
                $d_h_rod = 0;

                // Potrebna električna energija za delovanje kotla Ww,g,aux
                // enačba 157
                $W_w_g_aux = $mocPomoznih100 * $t_w_100 * $stDniTSV +
                    $mocPomoznih0 * (24 - $t_w_100) * ($stDniTSV - $d_h_rod);

                $this->potrebnaElektricnaEnergija['tsv'][$mesec] =
                    ($this->potrebnaElektricnaEnergija['tsv'][$mesec] ?? 0) + $W_w_g_aux;

                $faktorLokacije = $this->lokacija == VrstaLokacijeNamestitve::OgrevanProstor ? 1 : 0;

                // enačba
                $this->vracljiveIzgubeAux[$mesec] =
                    ($this->vracljiveIzgubeAux[$mesec] ?? 0) + $W_w_g_aux * (1 - 0.4) * $faktorLokacije;
            }
        } else {
            $beta_h_g_test_Pint = $this->tip->vmesnaObremenitev();

            $Paux_g_Pn = $this->tip->mocPomoznihElektricnihNaprav($this->nazivnaMoc, 'polna');
            $Paux_g_Pint = $this->tip->mocPomoznihElektricnihNaprav($this->nazivnaMoc, 'vmesna');
            $Paux_g_P0 = $this->tip->mocPomoznihElektricnihNaprav($this->nazivnaMoc, 'min');

            foreach (array_keys(Calc::MESECI) as $mesec) {
                $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
                $stUr = $stDni * 24;

                // th – mesečne obratovalne ure – čas [h/M] (enačba 43)
                $stUrOgrevanje = $stUr * ($sistem->povprecnaObremenitev[$mesec] > 0.05 ?
                    1 :
                    $sistem->povprecnaObremenitev[$mesec] / 0.05);

                // Moč pomožnih električnih naprav za kotel v odvisnosti od obremenitve kotla
                if ($this->beta_h_g[$mesec] < $beta_h_g_test_Pint) {
                    // enačba 103a
                    $Paux_g_i = $this->beta_h_g[$mesec] / $beta_h_g_test_Pint *
                        ($Paux_g_Pint - $Paux_g_P0) + $Paux_g_P0;
                } else {
                    // enačba 103b
                    $Paux_g_i = ($this->beta_h_g[$mesec] - $beta_h_g_test_Pint) / (1 - $beta_h_g_test_Pint) *
                        ($Paux_g_Pn - $Paux_g_Pint) + $Paux_g_Pint;
                }

                // enačba 158
                $casZaPripravoTSV = ($this->toplotneIzgube['tsv'][$mesec] ?? 0) / ($this->nazivnaMoc * $stDni);

                // todo:
                $stDniUporabeCone = 365;

                // enačba 102
                $potrebnaElektricnaEnergija = $Paux_g_i *
                    ($stUrOgrevanje - $casZaPripravoTSV * $stDni * $stDniUporabeCone / 365) +
                    $Paux_g_P0 * (24 * $stDni - $stUrOgrevanje);

                $this->vneseneIzgube['ogrevanje'][$mesec] = $vneseneIzgube[$mesec];
                $this->potrebnaElektricnaEnergija['ogrevanje'][$mesec] = $potrebnaElektricnaEnergija;

                // enačba 106
                $this->vracljiveIzgubeAux[$mesec] = $potrebnaElektricnaEnergija *
                    (1 - $this->tip->faktorRedukcijeVrnjeneEnergije($this->lokacija)) * (1 - 0.4);
            }
        }
    }

    /**
     * Uporabljena obnovljiva energija iz okolja
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function obnovljivaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->obnovljivaEnergija[$namen][] = 0;
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

        $sistem->porociloNizi = $this->porociloNizi;

        $sistem->porociloPodatki = [
            new TSSPorociloPodatek(
                'η<sub>H,gen,Pn</sub>',
                'Izkoristek polne obremenitve',
                $this->tip->izkoristekPolneObremenitve($this->nazivnaMoc),
                '-',
                3
            ),
            new TSSPorociloPodatek(
                'η<sub>H,gen,Pint</sub>',
                'Izkoristek vmesne obremenitve',
                $this->tip->izkoristekVmesneObremenitve($this->nazivnaMoc),
                '-',
                3
            ),
        ];

        return $sistem;
    }
}
