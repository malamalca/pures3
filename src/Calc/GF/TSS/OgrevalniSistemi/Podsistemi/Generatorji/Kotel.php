<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji;

use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Izbire\VrstaLokacijeNamestitve;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Izbire\VrstaRegulacijeKotla;
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
    public function potrebnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];
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

            // th – mesečne obratovalne ure – čas [h/M] (enačba 43)
            $steviloUr = $stUr * ($sistem->povprecnaObremenitev[$mesec] > 0.05 ?
                1 :
                $sistem->povprecnaObremenitev[$mesec] / 0.05);

            $Fc = $this->regulacija->faktorRegulacije($mesec, $cona, $okolje);

            // povprečna temperatura ogreval [°C]
            $T_h_em = $rezimRazvoda->projektnaTemperatura() +
                $Fc * ($cona->notranjaTOgrevanje - $rezimRazvoda->projektnaTemperatura());

            // obratovalna temperatura generatorja - enačba 83
            $T_h_g = max($T_h_em, $this->tip->temperaturnaOmejitev());

            // Izkoristek kotla pri 100% obremenitvi - enačba 90
            $ni_h_g_Pn_cor = $izk100 + $f_kor100 * ($this->tip->temperaturaKotlaPolneObremenitve() - $T_h_g);

            // toplotne izgube pri 100% obremenitvi
            $Q_h_g_Pn_cor =
                ($sistem->energent->maksimalniIzkoristek() - $ni_h_g_Pn_cor) / $ni_h_g_Pn_cor * $this->nazivnaMoc;

            // Izkoristek kotla pri delno (30%) obremenitvi - enačba 92
            $ni_h_g_Pint_cor = $izk30 + $f_kor30 * ($this->tip->temperaturaKotlaVmesneObremenitve() - $T_h_g);

            // toplotne izgube pri vmesni obremenitvi - enačba 93
            // Za kotle na plinasta in tekoča goriva: Qh g Pint = 0.3 * Qh g Pn
            $Q_h_g_Pint_cor = ($sistem->energent->maksimalniIzkoristek() -
                $ni_h_g_Pint_cor) / $ni_h_g_Pint_cor * $this->nazivnaMoc * 0.3;

            // Toplotne izgube pri 0% obremenitvi so določene za temperaturno razliko 30 K. - enačba 94q
            $Q_h_g_P0_cor =
                $this->tip->izgubeStandBy($this->nazivnaMoc) * pow(($T_h_g - $temperaturaOkolice) / 30, 1.25);

            // Razmerje toplotne obremenitve posameznega (i-tega) generatorja toplote h, g,i β pri paralelni
            // priključitvi j generatorjev. Vsi generatorji delujejo istočasno: obremenitev posameznega
            // generatorja ustreza razmerju skupne povprečne toplotne obremenitve:
            $this->beta_h_g[$mesec] = $steviloUr == 0 ? 0 : $vneseneIzgube[$mesec] / $steviloUr / $this->nazivnaMoc;

            // velja za kotle na tekoča in plinasta  goriva; za trda goriva 0.4 (iz excela)
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

            // Skupne toplotne izgube v času opazovanega časovnega intervala; enačba 99
            $Qh_g_l = $Q_h_g_l * $steviloUr;
            $toplotneIzgube = $Qh_g_l;

            // Toplotne izgube skozi ovoj generatorja toplote
            // enačba 106
            $this->vracljiveIzgube[$mesec] = $Q_h_g_P0_cor *
                (1 - $this->tip->delezVrnjenihIzgubSkoziOvoj($this->lokacija)) *
                $this->tip->faktorIzgubSkoziOvoj() * $steviloUr;

            if (empty($namen)) {
                $this->potrebnaEnergija[$mesec] = $toplotneIzgube;
            } else {
                $this->potrebnaEnergija[$namen][$mesec] = $toplotneIzgube;
            }
        }
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

        $beta_h_g_test_Pint = $this->tip->vmesnaObremenitev();

        $Paux_g_Pn = $this->tip->mocPomoznihElektricnihNaprav($this->nazivnaMoc, 'polna');
        $Paux_g_Pint = $this->tip->mocPomoznihElektricnihNaprav($this->nazivnaMoc, 'vmesna');
        $Paux_g_P0 = $this->tip->mocPomoznihElektricnihNaprav($this->nazivnaMoc, 'min');

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = $stDni * 24;

            // th – mesečne obratovalne ure – čas [h/M] (enačba 43)
            $steviloUr = $stUr * ($sistem->povprecnaObremenitev[$mesec] > 0.05 ?
                1 :
                $sistem->povprecnaObremenitev[$mesec] / 0.05);

            // Moč pomožnih električnih naprav za kotel v odvisnosti od obremenitve kotla
            if ($this->beta_h_g[$mesec] < $beta_h_g_test_Pint) {
                // enačba 103a
                $Paux_g_i = $this->beta_h_g[$mesec] / $beta_h_g_test_Pint * ($Paux_g_Pint - $Paux_g_P0) + $Paux_g_P0;
            } else {
                // enačba 98b
                $Paux_g_i = ($this->beta_h_g[$mesec] - $beta_h_g_test_Pint) / (1 - $beta_h_g_test_Pint) *
                    ($Paux_g_Pn - $Paux_g_Pint) + $Paux_g_Pint;
            }

            $potrebnaElektricnaEnergija = $Paux_g_i * $steviloUr + $Paux_g_P0 * (24 * $stDni - $steviloUr);

            if (empty($namen)) {
                $this->vneseneIzgube[$mesec] = $vneseneIzgube[$mesec];
                $this->potrebnaElektricnaEnergija[$mesec] = $potrebnaElektricnaEnergija;
            } else {
                $this->vneseneIzgube[$namen][$mesec] = $vneseneIzgube[$mesec];
                $this->potrebnaElektricnaEnergija[$namen][$mesec] = $potrebnaElektricnaEnergija;
            }

            $this->vracljiveIzgubeAux[$mesec] = $potrebnaElektricnaEnergija *
                (1 - $this->tip->faktorRedukcijeVrnjeneEnergije($this->lokacija)) * 0.6;
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

        $sistem->porociloNizi = [
            new TSSPorociloNiz(
                '&beta;<sub>H,gen</sub>',
                'Razmerje toplotne obremenitve posameznega (i-tega) generatorja toplote.',
                $this->beta_h_g,
                2
            ),
        ];

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
