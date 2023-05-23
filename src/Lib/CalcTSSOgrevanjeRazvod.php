<?php
declare(strict_types=1);

namespace App\Lib;

use App\Core\Configure;
use App\Core\Log;

class CalcTSSOgrevanjeRazvod
{
    /**
     * Analiza razvodnega sistema
     *
     * @param \StdClass $razvod Podatki razvoda
     * @param \StdClass $sistem Podatki sistema
     * @param \StdClass $cona Podatki cone
     * @param \StdClass $okolje Podatki okolja
     * @return void
     */
    public static function analizaRazvoda($razvod, $sistem, $cona, $okolje)
    {
        // iskanje končnega prenosnika zaradi temperaturnega režima; 
        // lahko da ne obstaja, to bi pomenilo toplozračno ogrevanje
        $razvod->idPrenosnika = $razvod->idPrenosnika ?? null;
        $prenosnik = null;
        if (!empty($sistem->prenosniki)) {
            foreach ($sistem->prenosniki as $p) {
                if (!empty($p->id) && ($p->id == $razvod->idPrenosnika)) {
                    $prenosnik = $p;
                }
            }
        }

        self::analizaRazvodaPotrebnaElektricnaEnergija($prenosnik, $razvod, $sistem, $cona);
        self::analizaRazvodaToplotneIzgube($prenosnik, $razvod, $sistem, $cona);
    }

    /**
     * Analiza razvodnega sistema :: Analiza električne energije
     * 6.1 Potrebna električna energija za razvodni podsistem
     *
     * @param \StdClass $razvod Podatki razvoda
     * @param \StdClass $sistem Podatki sistema
     * @return void
     */
    public static function analizaRazvodaPotrebnaElektricnaEnergija($prenosnik, $razvod, $sistem, $cona)
    {
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // OBTOČNA ČRPALKA
        $razvod->crpalka = $razvod->crpalka ?? new \StdClass();

        $razvod->crpalka->regulacija = $razvod->crpalka->regulacija ?? 'brezRegulacije';
        if (!in_array($razvod->crpalka->regulacija, array_keys(Configure::read('lookups.crpalke.regulacija')))) {
            throw new \Exception('Regulacija obtočne črpalke za razvod je neveljavna.');
        }

        $jeZnanaCrpalka = !empty($razvod->crpalka->moc);
        $izracunanaMocCrpalke = self::izracunHidravlicneMoci($razvod, $prenosnik, $sistem, $cona);
        $razvod->crpalka->moc = $razvod->crpalka->moc ?? $izracunanaMocCrpalke;

        // možnosti sta elektrika in toplota
        $razvod->navlazevanjeZraka = $razvod->navlazevanjeZraka ?? 'elektrika';

        // korekcijski faktor za hidravlično omrežje [-] 
        //      za dvocevni sistem: fsch = 1 
        //      za enocevni sistem: fsch = 8,6⋅m + 0,7 
        //          m – delež masnega pretoka skozi ogrevalo 
        $f_sch = ($razvod->vrsta == 'enocevni') ? 2.3 : 1;

        // korekcijski faktor za hidravlično uravnoteženje [-]
        //      za hidravlično uravnotežene sisteme: 1 
        //      za hidravlično neuravnotežene sisteme: 1,1 
        $f_abgl = empty($ravod->neuravnotezen) ? 1 : 1.1;

        // (enačba 68, spodaj)
        $faktorNovaObstojeca = 1;
        $fe_crpalke = $jeZnanaCrpalka ? $razvod->crpalka->moc / $izracunanaMocCrpalke : 1.25 + pow(200 / $izracunanaMocCrpalke, 0.5) * $faktorNovaObstojeca;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            // Izračun povprečnih obremenitev podsistemov 
            $betaH = $sistem->izgube[$mesec] / ($sistem->standardnaMoc * 24 * $stDni);

            // th – mesečne obratovalne ure – čas [h/M] (enačba 43) 
            $steviloUr = ($betaH > 0.05) ? 24 * $stDni : 24 * $stDni * $betaH / 0.05;

            // Qh,in,em - potrebna dovedena toplota v ogrevala [kWh] (enačba 60)
            $potrebnaToplotaOgrevala = $sistem->izgube[$mesec] + $sistem->izgubePrenosnikov[$mesec];

            // βh,d,a – povprečna letna obremenitev razvodnega omrežja [-] 
            // enačba (63)
            $betaI = ($sistem->standardnaMoc * $steviloUr) > 0 ? $potrebnaToplotaOgrevala / ($sistem->standardnaMoc * $steviloUr) : 0;

            // Wh,d,aux - Potrebna električna energija za razvodni podsistem 
            $razvod->potrebnaElektricnaEnergija[$mesec] = 0;

            if ($sistem->vrsta == 'toplovodni' || $sistem->vrsta == 'biomasa') {
                if ($betaI > 0) {
                    // tabela je del enačbe (68)
                    $Cp = Configure::read('lookups.crpalke.regulacija.' . $razvod->crpalka->regulacija);

                    // eh,d,aux - faktor rabe električne energije črpalke
                    // enačba (68)
                    $razvod->faktorRabeEnergije[$mesec] = $fe_crpalke * ($Cp['Cp1'] + $Cp['Cp2'] / $betaI);

                    // Wh,d,hydr - potrebna hidravlična energija
                    // enačba (64)
                    $razvod->potrebnaHidravlicnaEnergija[$mesec] = $razvod->crpalka->moc / 1000 * $betaI * $steviloUr * $f_sch * $f_abgl;

                    // Wh,d,aux - Potrebna električna energija za razvodni podsistem 
                    // za sisteme brez prekinitve
                    // enačba (61)
                    $razvod->potrebnaElektricnaEnergija[$mesec] = $razvod->potrebnaHidravlicnaEnergija[$mesec] * $razvod->faktorRabeEnergije[$mesec];
                    
                    // TODO: kaj pa za sisteme s prekinitvijo
                    // za prekinitev ogrevanja je korekturni faktor 1
                    //$korekturniFaktorPriZnizanju = 0.6;
                    // mesečno število ur ogrevanja
                    //$steviloUrOgrevanja = $steviloUr;
                    // enačba (69)
                    //$razvod->potrebnaElektricnaEnergija2[$mesec] = $razvod->potrebnaElektricnaEnergija[$mesec] *
                    //    (1.03 * $steviloUr + $korekturniFaktorPriZnizanju * $steviloUrOgrevanja) / $steviloUrOgrevanja;
                    //$vrnjenaElektricnaEnergija = 0.25 * $razvod->potrebnaElektricnaEnergija[$mesec];
                }
            }

            $razvod->vrnjenaElektricnaEnergijaVOgrevanje[$mesec] = 0.25 * $razvod->potrebnaElektricnaEnergija[$mesec];
            $razvod->vracljivaElektricnaEnergijaVZrak[$mesec] = 0.25 * $razvod->potrebnaElektricnaEnergija[$mesec];
            $razvod->vrnjenaToplotaVOkolico[$mesec] = 0.25 * $razvod->potrebnaElektricnaEnergija[$mesec];
        }
    }

    /**
     * Analiza razvodnega sistema :: Analiza toplotnih izgub
     *
     * @param \StdClass $razvod Podatki razvoda
     * @param \StdClass $sistem Podatki sistema
     * @return void
     */
    public static function analizaRazvodaToplotneIzgube($prenosnik, $razvod, $sistem, $cona)
    {
        // privzeta vrsta je "končni prenosnik" .. celice AC298
        $vrstaOgrevala = self::vrstaOgrevala($prenosnik, $sistem);
        $n_exp_ogr = Configure::read('lookups.faktorjiZaVrstoOgrevala.n_exp_ogr.' . $vrstaOgrevala);

        // srednja temperatura v prenosniku
        // stolpci so ploskovno|toplovodno+elektrika,  ostalo
        $srednjaTLookup = [
            '35/30' => [32.5, 35],
            '40/30' => [35, 50],
            '55/45' => [50, 62.5],
        ];

        // to velja za zrak-zrak - če ni definirana vrsta
        $srednjaT = 20;
        if (!empty($prenosnik)) {
            if ($sistem->vrsta == 'toplovodni' || $sistem->energent == 'elektrika') {
                $srednjaT = $srednjaTLookup[$prenosnik->rezim][0];
            } else {
                $srednjaT = $srednjaTLookup[$prenosnik->rezim][1];
            }
        }

        self::izracunDolzinCeviRazvoda($razvod, $cona);

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            // Izračun povprečnih obremenitev podsistemov 
            $betaH = $sistem->izgube[$mesec] / ($sistem->standardnaMoc * 24 * $stDni);

            // th – mesečne obratovalne ure – čas [h/M] (enačba 43) 
            $steviloUr = ($betaH > 0.05) ? 24 * $stDni : 24 * $stDni * $betaH / 0.05;

            // Qh,in,em - potrebna dovedena toplota v ogrevala [kWh] (enačba 60)
            $potrebnaToplotaOgrevala = $sistem->izgube[$mesec] + $sistem->izgubePrenosnikov[$mesec];

            // βh,d,a – povprečna letna obremenitev razvodnega omrežja [-] 
            // enačba (63)
            $betaI = ($sistem->standardnaMoc * $steviloUr) > 0 ? $potrebnaToplotaOgrevala / ($sistem->standardnaMoc * $steviloUr) : 0;

            // θ m - povprečna temperatura ogrevnega medija [°C]
            // enačba (39)
            $temperaturaRazvoda = $betaI > 0 ? (($srednjaT - 20) * pow($betaI, 1 / $n_exp_ogr) + 20) : 20;

            // θ a,i - temperatura okolice v i-ti coni, kjer so nameščene cevi razreda V, S ali A [°C]

            $izgubeZnotrajOvoja = 
                ($razvod->ceviHorizontaliVodi->Ucevi * ($razvod->ceviHorizontaliVodi->dolzina + $razvod->ceviHorizontaliVodi->dolzinaEqui) * $razvod->ceviHorizontaliVodi->delezVOgrevaniConi +
                $razvod->ceviDvizniVodi->Ucevi * $razvod->ceviDvizniVodi->dolzina * $razvod->ceviDvizniVodi->delezVOgrevaniConi +
                $razvod->ceviPrikljucniVodi->Ucevi * $razvod->ceviPrikljucniVodi->dolzina) * $steviloUr * ($temperaturaRazvoda - $cona->notranjaTOgrevanje) / 1000;

            $izgubeZunajOvoja = 
                ($razvod->ceviHorizontaliVodi->Ucevi * ($razvod->ceviHorizontaliVodi->dolzina + $razvod->ceviHorizontaliVodi->dolzinaEqui) * (1 - $razvod->ceviHorizontaliVodi->delezVOgrevaniConi) +
                $razvod->ceviDvizniVodi->Ucevi * $razvod->ceviDvizniVodi->dolzina * (1 - $razvod->ceviDvizniVodi->delezVOgrevaniConi)) *
                $steviloUr * ($temperaturaRazvoda - $cona->zunanjaT) / 1000;

            
            $razvod->skupneToplotneIzgube[$mesec] = $izgubeZnotrajOvoja + $izgubeZunajOvoja;

            $razvod->vrnjenaToplotaVOkolico[$mesec] += $razvod->skupneToplotneIzgube[$mesec];
        }
    }

    public static function vrstaOgrevala($prenosnik, $sistem) {
        $vrstaOgrevala = 3;
        if (!empty($prenosnik) && $sistem->vrsta == 'toplovodni') {
            switch ($prenosnik->vrsta) {
                case 'radiatorji':
                case 'konvektorji':
                    $vrstaOgrevala = 1;
                    break;
                case 'ploskovnaOgrevala':
                    $vrstaOgrevala = 2;
                    break;
            }
        }

        return $vrstaOgrevala;
    }

    public static function izracunDolzinCeviRazvoda($razvod, $cona) {
        $izolacijaTipi = ['izolirane', 'neizoliraneVZunanjiIzoliraniSteni', 'neizoliraneVZunanjiNeizoliraniSteni', 'neizoliraneVZraku', 'neizoliraneVNotranjiSteni'];

        $povrsinaEtaze = $cona->dolzina * $cona->sirina;

        // Lv
        if (empty($razvod->ceviHorizontaliVodi)) {
            $razvod->ceviHorizontaliVodi = new \StdClass();
        }

        $razvod->ceviHorizontaliVodi->delezVOgrevaniConi = $razvod->ceviHorizontaliVodi->delezVOgrevaniConi ?? 1;

        $razvod->ceviHorizontaliVodi->izolacija = ($razvod->ceviHorizontaliVodi->izolacija) ?? 'izolirane';
        if (!in_array($razvod->ceviHorizontaliVodi->izolacija, $izolacijaTipi)) {
            $razvod->ceviHorizontaliVodi->izolacija = 'izolirane';
        }

        $razvod->ceviHorizontaliVodi->dolzinaEqui = $razvod->ceviHorizontaliVodi->dolzinaEqui ?? 0;
        if (empty($razvod->ceviHorizontaliVodi->dolzina)) {
            // avtomatski izračun dolžine
            switch ($razvod->vrsta) {
                case 'dvocevniZunaj':
                    $razvod->ceviHorizontaliVodi->dolzina = 2 * $cona->dolzina + 0.01625 * $cona->dolzina * pow($cona->sirina, 2);
                    break;
                case 'dvocevniZnotraj':
                    $razvod->ceviHorizontaliVodi->dolzina = 2 * $cona->dolzina + 0.0325 * $cona->dolzina * $cona->sirina + 6;
                    break;
                case 'enocevni':
                    $razvod->ceviHorizontaliVodi->dolzina = 2 * $cona->dolzina + 0.0325 * $cona->dolzina * $cona->sirina + 6;
                    break;
            }
        }

        if (empty($razvod->ceviHorizontaliVodi->Ucevi)) {
            switch ($razvod->ceviHorizontaliVodi->izolacija ) {
                case 'izolirane':
                    $razvod->ceviHorizontaliVodi->Ucevi = 0.3;
                    break;
                default: 
                    $razvod->ceviHorizontaliVodi->Ucevi = $povrsinaEtaze > 200 ? ($povrsinaEtaze > 500 ? 3 : 2) : 1;
                    break;
            }
        }

        // "Ls"
        if (empty($razvod->ceviDvizniVodi)) {
            $razvod->ceviDvizniVodi = new \StdClass();
        }

        $razvod->ceviDvizniVodi->izolacija = ($razvod->ceviDvizniVodi->izolacija) ?? 'izolirane';
        if (!in_array($razvod->ceviDvizniVodi->izolacija, $izolacijaTipi)) {
            $razvod->ceviDvizniVodi->izolacija = 'izolirane';
        }

        if (empty($razvod->ceviDvizniVodi->dolzina)) {
            // avtomatski izračun dolžine
            switch ($razvod->vrsta) {
                case 'dvocevniZunaj':
                    $razvod->ceviDvizniVodi->dolzina = 0.025 * $povrsinaEtaze * $cona->steviloEtaz * $cona->etaznaVisina;
                    break;
                case 'dvocevniZnotraj':
                    $razvod->ceviDvizniVodi->dolzina = 0.025 * $povrsinaEtaze * $cona->steviloEtaz * $cona->etaznaVisina;
                    break;
                case 'enocevni':
                    $razvod->ceviDvizniVodi->dolzina = 0.025 * $povrsinaEtaze * $cona->steviloEtaz * $cona->etaznaVisina +
                        ($cona->dolzina + $cona->sirina) * $cona->steviloEtaz;
                    break;
            }
        }

        if (empty($razvod->ceviDvizniVodi->Ucevi)) {
            switch ($razvod->ceviDvizniVodi->izolacija ) {
                case 'izolirane':
                    $razvod->ceviDvizniVodi->Ucevi = 0.3;
                    if (empty($razvod->ceviDvizniVodi->delezVOgrevaniConi)) {
                        $razvod->ceviDvizniVodi->delezVOgrevaniConi = 1;
                    }
                    break;
                case 'neizoliraneVZunanjiIzoliraniSteni':
                    $razvod->ceviDvizniVodi->Ucevi = 0.75;
                    if (empty($razvod->ceviDvizniVodi->delezVOgrevaniConi)) {
                        $razvod->ceviDvizniVodi->delezVOgrevaniConi = 0.73;
                    }
                    break;
                case 'neizoliraneVZunanjiNeizoliraniSteni':
                    if (empty($razvod->ceviDvizniVodi->delezVOgrevaniConi)) {
                        $razvod->ceviDvizniVodi->delezVOgrevaniConi = 0.59;
                    }
                    $razvod->ceviDvizniVodi->Ucevi = 1.35;
                    break;
                default: 
                    $razvod->ceviDvizniVodi->Ucevi = $povrsinaEtaze > 200 ? ($povrsinaEtaze > 500 ? 3 : 2) : 1;
                    break;
            }
        }

        $razvod->ceviDvizniVodi->delezVOgrevaniConi = $razvod->ceviDvizniVodi->delezVOgrevaniConi ?? 1;

        // "La - ceviPrikljucniVodi"
        if (empty($razvod->ceviPrikljucniVodi)) {
            $razvod->ceviPrikljucniVodi = new \StdClass();
        }

        $razvod->ceviPrikljucniVodi->delezVOgrevaniConi = $razvod->ceviPrikljucniVodi->delezVOgrevaniConi ?? 1;

        $razvod->ceviPrikljucniVodi->izolacija = ($razvod->ceviPrikljucniVodi->izolacija) ?? 'izolirane';
        if (!in_array($razvod->ceviPrikljucniVodi->izolacija, $izolacijaTipi)) {
            $razvod->ceviPrikljucniVodi->izolacija = 'izolirane';
        }

        if (empty($razvod->ceviPrikljucniVodi->dolzina)) {
            // avtomatski izračun dolžine
            switch ($razvod->vrsta) {
                case 'dvocevniZunaj':
                case 'dvocevniZnotraj':
                    $razvod->ceviPrikljucniVodi->dolzina = 0.55 * $povrsinaEtaze * $cona->steviloEtaz;
                    break;
                case 'enocevni':
                    $razvod->ceviPrikljucniVodi->dolzina = 0.1 * $povrsinaEtaze * $cona->steviloEtaz;
                    break;
            }
        }

        if (empty($razvod->ceviPrikljucniVodi->Ucevi)) {
            switch ($razvod->ceviPrikljucniVodi->izolacija ) {
                case 'izolirane':
                    $razvod->ceviPrikljucniVodi->Ucevi = 0.3;
                    break;
                default: 
                    $razvod->ceviPrikljucniVodi->Ucevi = $povrsinaEtaze > 200 ? ($povrsinaEtaze > 500 ? 3 : 2) : 1;
                    break;
            }
        }
    }

    public static function izracunHidravlicneMoci($razvod, $prenosnik, $sistem, $cona)
    {
        // lc = 10 za dvocevni sistem 
        // lc = L+B za enocevni sistem
        $lc = 10;
        if ($razvod->vrsta == 'enocevni') {
            $lc = $cona->dolzina + $cona->sirina;
        }

        $Lmax = 2 * ($cona->dolzina + $cona->sirina / 2 + $cona->etaznaVisina * $cona->steviloEtaz + $lc);

        $vrstaOgrevala = self::vrstaOgrevala($prenosnik, $sistem);

        // ΔpFBH – dodatek pri ploskovnem ogrevanju, če ni proizvajalčevega podatka je 25 kPa vključno z ventili in razvodom (kPa)
        $deltaP_FBH = Configure::read('lookups.faktorjiZaVrstoOgrevala.dP_FBH.' . $vrstaOgrevala);

        // ΔpWE – tlačni padec generatorja toplote:
        //      standardni kotel: 1 kPa
        //      stenski kotel: 20 kPa
        //      kondenzacijski kotel: 20 kPa
        $deltaP_WE = 20;

        // tlačni padec generatorja toplote
        // enačba (67)
        $deltaP = 0.13 * $Lmax + 2 + $deltaP_FBH + $deltaP_WE;

        $deltaTHKLookup = [
            '35/30' => [5, 10],
            '40/30' => [10, 15],
            '55/45' => [10, 15],
        ];

        // ΔθHK – temperaturna razlika pri standardnem temperaturnem režimu ogrevalnega sistema [°C] (enačba 38) 
        // 0 velja za zrak-zrak - oz. če ni definirana vrsta
        $deltaT_HK = 0;
        if (!empty($prenosnik)) {
            if ($sistem->vrsta == 'toplovodni' || $sistem->energent == 'elektrika') {
                $deltaT_HK = $deltaTHKLookup[$prenosnik->rezim][0];
            } else {
                $deltaT_HK = $deltaTHKLookup[$prenosnik->rezim][1];
            }
        }

        if ($sistem->vrsta == 'sevala' && $sistem->energent == 'elektrika') {
            $deltaT_HK = 0;
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
}
