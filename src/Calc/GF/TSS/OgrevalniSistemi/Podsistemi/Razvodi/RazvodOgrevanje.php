<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Razvodi;

use App\Calc\GF\TSS\OgrevalniSistemi\LokalniOgrevalniSistemNaBiomaso;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Razvodi\Izbire\RazvodAbstractProperties;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Razvodi\Izbire\VrstaNamenaCevi;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Razvodi\Izbire\VrstaRazvodnihCevi;
use App\Calc\GF\TSS\OgrevalniSistemi\ToplovodniOgrevalniSistem;
use App\Core\Configure;
use App\Lib\Calc;

abstract class RazvodOgrevanje extends Razvod
{
    public ?\stdClass $crpalka;
    public string $navlazevanjeZraka = 'elektrika';

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

        $this->horizontalniVod =
            new ElementRazvoda(
                VrstaRazvodnihCevi::HorizontalniRazvod,
                VrstaNamenaCevi::Ogrevanje,
                $config->ceviHorizontaliVodi
            );
        $this->dvizniVod =
            new ElementRazvoda(
                VrstaRazvodnihCevi::DvizniVod,
                VrstaNamenaCevi::Ogrevanje,
                $config->ceviDvizniVodi
            );
        $this->prikljucniVod =
            new ElementRazvoda(
                VrstaRazvodnihCevi::PrikljucniVod,
                VrstaNamenaCevi::Ogrevanje,
                $config->ceviPrikljucniVodi
            );

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // OBTOČNA ČRPALKA
        if (!empty($config->crpalka)) {
            $this->crpalka = $config->crpalka;

            $this->crpalka->regulacija = $config->crpalka->regulacija ?? 'brezRegulacije';
            if (!in_array($this->crpalka->regulacija, array_keys(Configure::read('lookups.crpalke.regulacija')))) {
                throw new \Exception('Regulacija obtočne črpalke za razvod je neveljavna.');
            }
        }
    }

    /**
     * Analiza podsistema
     *
     * @param array $potrebnaEnergija Potrebna energija predhodnih TSS
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
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
     * Izračun toplotnih izgub končnega prenosnika
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $prenosnik = $params['prenosnik'];
        $rezim = $params['rezim'];

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = 24 * $stDni;

            // th – mesečne obratovalne ure – čas [h/M] (enačba 43)
            $steviloUr = $stUr * ($sistem->povprecnaObremenitev[$mesec] > 0.05 ?
                1 :
                $sistem->povprecnaObremenitev[$mesec] / 0.05);

            // Qh,in,em - potrebna dovedena toplota v ogrevala [kWh] (enačba 60)
            $potrebnaToplotaOgrevala = $vneseneIzgube[$mesec];

            // βh,d,a – povprečna letna obremenitev razvodnega omrežja [-]
            // enačba (63)
            $betaI = $sistem->standardnaMoc * $steviloUr > 0 ?
                $potrebnaToplotaOgrevala / ($sistem->standardnaMoc * $steviloUr) :
                0;

            // θ m - povprečna temperatura ogrevnega medija [°C]
            // enačba (39)
            $srednjaT = $rezim->srednjaTemperatura($sistem);

            $exponentOgrevala = $prenosnik->exponentOgrevala ?? 1;
            $temperaturaRazvoda = $betaI > 0 ? (($srednjaT - $cona->notranjaTOgrevanje) *
                pow($betaI, 1 / $exponentOgrevala) + $cona->notranjaTOgrevanje) : $cona->notranjaTOgrevanje;

            $izgubeZnotrajOvoja = (
                $this->horizontalniVod->toplotneIzgube($this, $cona, $this->horizontalniVod->delezVOgrevaniConi) +
                $this->dvizniVod->toplotneIzgube($this, $cona, $this->dvizniVod->delezVOgrevaniConi) +
                $this->prikljucniVod->toplotneIzgube($this, $cona, $this->prikljucniVod->delezVOgrevaniConi)
                ) *
                $steviloUr * ($temperaturaRazvoda - $cona->notranjaTOgrevanje) / 1000;

            $temperaturaIzvenOvoja = 13;
            $izgubeZunajOvoja = (
                $this->horizontalniVod->toplotneIzgube($this, $cona, 1 - $this->horizontalniVod->delezVOgrevaniConi) +
                $this->dvizniVod->toplotneIzgube($this, $cona, 1 - $this->dvizniVod->delezVOgrevaniConi) +
                $this->prikljucniVod->toplotneIzgube($this, $cona, 1 - $this->prikljucniVod->delezVOgrevaniConi)
                ) *
                $steviloUr * ($temperaturaRazvoda - $temperaturaIzvenOvoja) / 1000;

            $this->toplotneIzgube[$mesec] = $izgubeZnotrajOvoja + $izgubeZunajOvoja;
            $this->vracljiveIzgube[$mesec] = $izgubeZnotrajOvoja;
        }

        return $this->toplotneIzgube;
    }

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function potrebnaElektricnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        /** @var \App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\KoncniPrenosnik $prenosnik */
        $prenosnik = $params['prenosnik'] ?? null;
        $rezim = $params['rezim'] ?? null;

        if (!empty($this->crpalka)) {
            $hidravlicnaMoc = $this->izracunHidravlicneMoci($prenosnik, $rezim, $sistem, $cona);
            $fe_crpalke = $this->izracunFaktorjaRabeEnergijeCrpalke($hidravlicnaMoc);
            $this->crpalka->moc = $this->crpalka->moc ?? $hidravlicnaMoc;

            // možnosti sta elektrika in toplota
            // TODO:
            // $this->navlazevanjeZraka = $this->navlazevanjeZraka ?? 'elektrika';

            // f_sch - korekcijski faktor za hidravlično omrežje [-]
            //      za dvocevni sistem: fsch = 1
            //      za enocevni sistem: fsch = 8,6⋅m + 0,7
            //          m – delež masnega pretoka skozi ogrevalo
            $f_sch = $this->getProperty(RazvodAbstractProperties::f_sch);

            // korekcijski faktor za hidravlično uravnoteženje [-]
            //      za hidravlično uravnotežene sisteme: 1
            //      za hidravlično neuravnotežene sisteme: 1,1
            // TODO:
            $f_abgl = 1;

            foreach (array_keys(Calc::MESECI) as $mesec) {
                $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
                $stUr = 24 * $stDni;

                // th – mesečne obratovalne ure – čas [h/M] (enačba 43)
                $steviloUr = $stUr * ($sistem->povprecnaObremenitev[$mesec] > 0.05 ?
                    1 :
                    $sistem->povprecnaObremenitev[$mesec] / 0.05);

                // Qh,in,em - potrebna dovedena toplota v ogrevala [kWh] (enačba 60)
                $potrebnaToplotaOgrevala = $vneseneIzgube[$mesec];

                // βh,d,a – povprečna letna obremenitev razvodnega omrežja [-]
                // enačba (63)
                $betaI = $sistem->standardnaMoc * $steviloUr > 0 ?
                    $potrebnaToplotaOgrevala / ($sistem->standardnaMoc * $steviloUr) :
                    0;

                // Wh,d,aux - Potrebna električna energija za razvodni podsistem
                $potrebnaElektricnaEnergija[$mesec] = 0;

                if (
                    ($sistem instanceof ToplovodniOgrevalniSistem) ||
                    ($sistem instanceof LokalniOgrevalniSistemNaBiomaso)
                ) {
                    if ($betaI > 0) {
                        // tabela je del enačbe (68)
                        $Cp = Configure::read('lookups.crpalke.regulacija.' . $this->crpalka->regulacija);

                        // eh,d,aux - faktor rabe električne energije črpalke
                        // enačba (68)
                        $faktorRabeEnergije = $fe_crpalke * ($Cp['Cp1'] + $Cp['Cp2'] / $betaI);

                        // Wh,d,hydr - potrebna hidravlična energija
                        // enačba (64)
                        $potrebnaHidravlicnaEnergija = $this->crpalka->moc / 1000 *
                            $betaI * $steviloUr * $f_sch * $f_abgl;

                        // Wh,d,aux - Potrebna električna energija za razvodni podsistem
                        // za sisteme brez prekinitve
                        // enačba (61)
                        $this->potrebnaElektricnaEnergija[$mesec] = $potrebnaHidravlicnaEnergija * $faktorRabeEnergije;

                        // TODO: kaj pa za sisteme s prekinitvijo
                        // za prekinitev ogrevanja je korekturni faktor 1
                        //$korekturniFaktorPriZnizanju = 0.6;
                        // mesečno število ur ogrevanja
                        //$steviloUrOgrevanja = $steviloUr;
                        // enačba (69)
                        //$razvod->potrebnaElektricnaEnergija2[$mesec] = $razvod->potrebnaElektricnaEnergija[$mesec] *
                        //    (1.03 * $steviloUr + $korekturniFaktorPriZnizanju * $steviloUrOgrevanja) / $steviloUrOgrevanja;
                        //$vrnjenaElektricnaEnergija = 0.25 * $razvod->potrebnaElektricnaEnergija[$mesec];
                    } else {
                        $this->potrebnaElektricnaEnergija[$mesec] = 0;
                    }
                }

                $this->vracljiveIzgubeAux[$mesec] = 0.25 * $this->potrebnaElektricnaEnergija[$mesec];

                //$razvod->vrnjenaElektricnaEnergijaVOgrevanje[$mesec] = 0.25 * $razvod->potrebnaElektricnaEnergija[$mesec];
                //$razvod->vracljivaElektricnaEnergijaVZrak[$mesec] = 0.25 * $razvod->potrebnaElektricnaEnergija[$mesec];
                //$razvod->vrnjenaToplotaVOkolico[$mesec] = 0.25 * $razvod->potrebnaElektricnaEnergija[$mesec];
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
     * Izračun faktorja rabe energije črpalke e_h,d
     * (enačba 68, spodaj)
     *
     * @param float $hidravlicnaMoc Hidravlična moč
     * @return float
     */
    public function izracunFaktorjaRabeEnergijeCrpalke($hidravlicnaMoc)
    {
        // (enačba 68, spodaj)
        $faktorCrpalkaPoProjektu = 1;

        $fe_crpalke = /*!empty($this->crpalka->moc) ?
            $this->crpalka->moc / $hidravlicnaMoc :*/
            1.25 + pow(200 / $hidravlicnaMoc, 0.5) * $faktorCrpalkaPoProjektu;

        return $fe_crpalke;
    }

    /**
     * Izračun hidravlične moči črpalke
     *
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\KoncniPrenosnik $prenosnik Podatki prenosnika
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\Izbire\VrstaRezima|null $rezim Podatki režima
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @return float
     */
    public function izracunHidravlicneMoci($prenosnik, $rezim, $sistem, $cona)
    {
        $Lmax = $this->getProperty(RazvodAbstractProperties::Lmax, ['cona' => $cona]);

        // ΔpFBH – dodatek pri ploskovnem ogrevanju, če ni proizvajalčevega podatka je 25 kPa vključno z ventili in razvodom (kPa)
        $deltaP_FBH = $prenosnik->deltaP_FBH;

        // ΔpWE – tlačni padec generatorja toplote:
        //      standardni kotel: 1 kPa
        //      stenski kotel: 20 kPa
        //      kondenzacijski kotel: 20 kPa
        $deltaP_WE = 20;

        // tlačni padec generatorja toplote
        // enačba (67)
        $deltaP = 0.13 * $Lmax + 2 + $deltaP_FBH + $deltaP_WE;

        // ΔθHK – temperaturna razlika pri standardnem temperaturnem režimu ogrevalnega sistema [°C] (enačba 38)
        // 0 velja za zrak-zrak - oz. če ni definirana vrsta
        $deltaT_HK = 0;
        if (!empty($rezim)) {
            $deltaT_HK = $rezim->temperaturnaRazlikaStandardnegaRezima($sistem);
        }

        // V – volumski pretok ogrevnega medija [m3/h]
        // enačba (66)
        $volumskiPretok = 0;
        if ($deltaT_HK > 0) {
            $volumskiPretok = $sistem->standardnaMoc / (1.15 * $deltaT_HK);
        }

        // Hidravlična moč v načrtovani obratovalni točk
        $mocCrpalke = 0.2778 * $deltaP * $volumskiPretok;

        return $mocCrpalke;
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $sistem = parent::export();
        $sistem->crpalka = $this->crpalka;

        return $sistem;
    }
}
