<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire\RazvodAbstractProperties;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire\VrstaNamenaCevi;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire\VrstaRazvodnihCevi;
use App\Lib\Calc;

class RazvodTSV extends Razvod
{
    public string $sistem = 'toplavoda';
    public ?\stdClass $crpalka;

    public bool $prikljucniVodNaInstalacijskiSteni = false;

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        parent::parseConfig($config);

        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->horizontalniVod = new ElementRazvoda(
            VrstaRazvodnihCevi::HorizontalniRazvod,
            VrstaNamenaCevi::ToplaSanitarnaVoda,
            $config->ceviHorizontaliVodi
        );
        $this->dvizniVod = new ElementRazvoda(
            VrstaRazvodnihCevi::DvizniVod,
            VrstaNamenaCevi::ToplaSanitarnaVoda,
            $config->ceviDvizniVodi
        );
        $this->prikljucniVod = new ElementRazvoda(
            VrstaRazvodnihCevi::PrikljucniVod,
            VrstaNamenaCevi::ToplaSanitarnaVoda,
            $config->ceviPrikljucniVodi
        );

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // OBTOČNA ČRPALKA
        if (!empty($config->crpalka)) {
            $this->crpalka = $config->crpalka;
        }
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
    }

    /**
     * Izračun toplotnih izgub končnega prenosnika
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        if (isset($this->crpalka) && empty($this->crpalka->casDelovanja)) {
            // z – čas delovanja cirkulacijske črpalke (v urah na dan) [h]
            // enačba (142)
            $this->crpalka->casDelovanja = $this->izracunCasaDelovanjaCrpalke($cona);
            $steviloUrCrpalke = $this->crpalka->casDelovanja;
        } else {
            // todo: število ur kroženja vode glede na vrsto cone
            $steviloUrCrpalke = 2;
        }

        $steviloUrBrezCirkulacije = 24 - $steviloUrCrpalke;

        $povprecniUCevi = ($this->horizontalniVod->toplotneIzgube($this, $cona) +
            $this->dvizniVod->toplotneIzgube($this, $cona) +
            $this->prikljucniVod->toplotneIzgube($this, $cona)) / (
                $this->dolzinaCevi(VrstaRazvodnihCevi::DvizniVod, $cona) +
                $this->dolzinaCevi(VrstaRazvodnihCevi::PrikljucniVod, $cona) +
                $this->dolzinaCevi(VrstaRazvodnihCevi::HorizontalniRazvod, $cona)
            );

        $temperaturaIzvenOvoja = 13;
        $temperaturaCevovodaBrezCirkulacije = 25 * pow($povprecniUCevi, -0.2);
        $temperaturaCevovodaSCirkulacijo = 50;
        $temperaturaHladneVode = 10;
        $padecTemperatureVCirkulaciji = 5;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = 24 * $stDni;

            // Q_w,d,l, col – toplotne izgube skupnega dela razdelilnega omrežja [kWh]
            // (enačba 120)
            $izgubeZnotrajOvoja_Cirkulacija =
                ($this->horizontalniVod->toplotneIzgube($this, $cona, $this->horizontalniVod->delezVOgrevaniConi) +
                $this->dvizniVod->toplotneIzgube($this, $cona, $this->dvizniVod->delezVOgrevaniConi) +
                $this->prikljucniVod->toplotneIzgube($this, $cona, $this->prikljucniVod->delezVOgrevaniConi)) *
                $stDni * $steviloUrCrpalke *
                ($temperaturaCevovodaSCirkulacijo - $cona->notranjaTOgrevanje) / 1000;

            $izgubeZunajOvoja_Cirkulacija =
                ($this->horizontalniVod->toplotneIzgube($this, $cona, 1 - $this->horizontalniVod->delezVOgrevaniConi) +
                $this->dvizniVod->toplotneIzgube($this, $cona, 1 - $this->dvizniVod->delezVOgrevaniConi) +
                $this->prikljucniVod->toplotneIzgube($this, $cona, 1 - $this->prikljucniVod->delezVOgrevaniConi)) *
                $stDni * $steviloUrCrpalke *
                ($temperaturaCevovodaSCirkulacijo - $temperaturaIzvenOvoja) / 1000;

            $izgubeZnotrajOvoja_BrezCirkulacije =
                ($this->horizontalniVod->toplotneIzgube($this, $cona, $this->horizontalniVod->delezVOgrevaniConi) +
                $this->dvizniVod->toplotneIzgube($this, $cona, $this->dvizniVod->delezVOgrevaniConi) +
                $this->prikljucniVod->toplotneIzgube($this, $cona, $this->prikljucniVod->delezVOgrevaniConi)) *
                $stDni * $steviloUrBrezCirkulacije *
                ($temperaturaCevovodaBrezCirkulacije - $cona->notranjaTOgrevanje) / 1000;

            $izgubeZunajOvoja_BrezCirkulacije =
                ($this->horizontalniVod->toplotneIzgube($this, $cona, 1 - $this->horizontalniVod->delezVOgrevaniConi) +
                $this->dvizniVod->toplotneIzgube($this, $cona, 1 - $this->dvizniVod->delezVOgrevaniConi) +
                $this->prikljucniVod->toplotneIzgube($this, $cona, 1 - $this->prikljucniVod->delezVOgrevaniConi)) *
                $stDni * $steviloUrBrezCirkulacije *
                ($temperaturaCevovodaBrezCirkulacije - $temperaturaIzvenOvoja) / 1000;

            $this->toplotneIzgube[$mesec] = $izgubeZnotrajOvoja_Cirkulacija + $izgubeZunajOvoja_Cirkulacija +
                $izgubeZnotrajOvoja_BrezCirkulacije + $izgubeZunajOvoja_BrezCirkulacije;

            $this->vracljiveIzgube[$mesec] = $izgubeZnotrajOvoja_Cirkulacija + $izgubeZnotrajOvoja_BrezCirkulacije;
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
        if (!empty($this->crpalka)) {
            $fe_crpalke = $this->izracunFaktorjaRabeEnergijeCrpalke($cona, $okolje);
            $this->crpalka->moc = $this->crpalka->moc ?? $this->izracunHidravlicneMoci($cona, $okolje);

            // z – čas delovanja črpalke (v urah na dan) [h]
            // enačba (142)
            $steviloUrCrpalke = $this->izracunCasaDelovanjaCrpalke($cona);

            foreach (array_keys(Calc::MESECI) as $mesec) {
                $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
                $stUr = $stDni * 24;

                // ew,d,aux - faktor rabe električne energije črpalke
                // enačba (147)
                if (!empty($this->crpalka->regulacija)) {
                    $Cp = ['Cp1' => 0.5, 'Cp2' => 0.63];
                } else {
                    $Cp = ['Cp1' => 0.25, 'Cp2' => 0.94];
                }
                $faktorRabeEnergije = $fe_crpalke * $Cp['Cp1'] + $Cp['Cp2'];
                // TODO: Napaka v excelu???
                $faktorRabeEnergije = empty($this->crpalka->regulacija) ? 1.19 : 1.13;

                ////////////////////////////////////////////////////////////////////////////////////////////////////////
                // W_w,d,hydr - potrebna hidravlična energija
                // enačba (141)
                $potrebnaHidravlicnaEnergija = $this->crpalka->moc / 1000 *
                    $stDni * $steviloUrCrpalke;

                // W_w,d,aux - Potrebna električna energija za razvodni podsistem
                // enačba (140)
                $this->potrebnaElektricnaEnergija[$mesec] =
                    $potrebnaHidravlicnaEnergija * $fe_crpalke * $faktorRabeEnergije;

                // enačba (149)
                // todo: excel tega ne upošteva
                $this->vracljiveIzgubeTSV[$mesec] = 0.25 * $this->potrebnaElektricnaEnergija[$mesec];

                // Delež vrnjene energije v okoliški zrak
                // todo: upoštevaj možnost, da črpalka ni v ogrevanem prostoru/coni
                // enačba (150)
                $this->vracljiveIzgubeAux[$mesec] = 0.25 * $this->potrebnaElektricnaEnergija[$mesec];
            }
        } else {
            // brez črpalke
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $this->potrebnaElektricnaEnergija[$mesec] = 0;
                $this->vracljiveIzgubeAux[$mesec] = 0;
            }
        }

        return $this->potrebnaElektricnaEnergija;
    }

    /**
     * Izračun časa delovanja črpalke po enačbi (142)
     *
     * @param \stdClass $cona Podatki cone
     * @return float
     */
    public function izracunCasaDelovanjaCrpalke($cona)
    {
        $ret = 10 + 1 / (0.07 +
        (50 / (0.32 * $cona->dolzina * $cona->sirina * $cona->steviloEtaz * $cona->etaznaVisina)));

        if ($ret > 24) {
            $ret = 24;
        }

        return $ret;
    }

    /**
     * Izračun faktorja rabe energije črpalke e_w,d
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return float
     */
    public function izracunFaktorjaRabeEnergijeCrpalke($cona, $okolje)
    {
        // (enačba 68, spodaj)
        // (enačba 147, spodaj)
        $faktorCrpalkaPoProjektu = 1;

        $fe_crpalke = !empty($this->crpalka->moc) ?
            $this->crpalka->moc / $this->izracunHidravlicneMoci($cona, $okolje) :
            1.25 + pow(200 / $this->izracunHidravlicneMoci($cona, $okolje), 0.5) * $faktorCrpalkaPoProjektu;

        return $fe_crpalke;
    }

    /**
     * Izračun potrebne električne energije
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return float
     */
    public function izracunHidravlicneMoci($cona, $okolje)
    {
        $Lmax = $this->getProperty(RazvodAbstractProperties::Lmax, ['cona' => $cona]);

        // ΔpRV,TH - tlačni padec vgrajenih armature (npr. protipovratni ventil – indeks RV, termostatni ventil – indeks TH) [kPa]
        // ΔpRV,TH = 12 kPa prevzeta vrednost
        $deltaP_RV = 12;

        // Δp_App – tlačni padec na generatorju toplote [kPa]
        //      sistem s hranilnikom: ΔpApp = 1 kPa
        //      pretočni sistem: ΔpApp = 15 kPa
        // TODO:
        $deltaP_App = 1;

        // tlačni padec generatorja toplote
        // enačba (146)
        $deltaP = 0.10 * $Lmax + $deltaP_RV + $deltaP_App;

        // Δθ_Z – maksimalne dopustne temperature razlike vode v cirkulacijski zank
        $deltaT_Z = 5;

        // toplotnih izgub v cirkulacijski zanki
        $Q_t = ($this->horizontalniVod->toplotneIzgube($this, $cona) +
            $this->dvizniVod->toplotneIzgube($this, $cona) +
            $this->prikljucniVod->toplotneIzgube($this, $cona)) * (57.5 - $cona->notranjaTOgrevanje);

        // V – volumski pretok ogrevnega medija [m³/h]
        // enačba (66)
        $volumskiPretok = $Q_t / (1.15 * $deltaT_Z) / 1000;

        // Hidravlična moč v načrtovani obratovalni točk
        $mocCrpalke = 0.2778 * $deltaP * $volumskiPretok;

        return $mocCrpalke;
    }

    /**
     * Vrne dolžino cevi za podano vrsto razvodnih cevi
     *
     * @param \App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire\VrstaRazvodnihCevi $vrsta Vrsta razvodne cevi
     * @param \stdClass $cona Podatki cone
     * @return float
     */
    public function dolzinaCevi(VrstaRazvodnihCevi $vrsta, $cona)
    {
        switch ($vrsta) {
            case VrstaRazvodnihCevi::HorizontalniRazvod:
                if (!empty($this->crpalka)) {
                    return 2 * $cona->dolzina + 0.0125 * $cona->dolzina * $cona->sirina;
                } else {
                    return $cona->dolzina + 0.0625 * $cona->dolzina * $cona->sirina;
                }
            case VrstaRazvodnihCevi::DvizniVod:
                if (!empty($this->crpalka)) {
                    return 0.075 * $cona->dolzina * $cona->sirina * $cona->steviloEtaz * $cona->etaznaVisina;
                } else {
                    return 0.038 * $cona->dolzina * $cona->sirina * $cona->steviloEtaz * $cona->etaznaVisina;
                }
            case VrstaRazvodnihCevi::PrikljucniVod:
                if ($this->prikljucniVodNaInstalacijskiSteni) {
                    return 0.05 * $cona->dolzina * $cona->sirina * $cona->steviloEtaz;
                } else {
                    return 0.075 * $cona->dolzina * $cona->sirina * $cona->steviloEtaz;
                }
        }
    }

    /**
     * Vrne zahtevano fiksno vrednost konstante/količine
     *
     * @param \App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire\RazvodAbstractProperties $property Količina/konstanta
     * @param array $options Dodatni parametri
     * @return int|float
     */
    public function getProperty(RazvodAbstractProperties $property, $options = [])
    {
        switch ($property) {
            case RazvodAbstractProperties::Lmax:
                $cona = $options['cona'];
                $Lmax = 2 * ($cona->dolzina + 2.5 + $cona->steviloEtaz + $cona->etaznaVisina);

                return $Lmax;
            case RazvodAbstractProperties::f_sch:
                return 1;
        }
    }
}
