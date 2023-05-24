<?php

namespace App\Calc\TSS\Razvodi;

use App\Core\Configure;
use App\Lib\Calc;

enum VrstaRazvodnihCevi: string {
    use \App\Lib\Traits\GetOrdinalTrait;

    case HorizontalniRazvod = 'ceviHorizontaliVodi';
    case DvizniVod = 'ceviDvizniVodi';
    case PrikljucniVod = 'ceviPrikljucniVodi';
}

enum VrstaIzolacijeCevi: string {
    use \App\Lib\Traits\GetOrdinalTrait;

    case IzoliraneCevi = 'izolirane';
    case NeizoliraneCeviVIzoliraniZunanjiSteni = 'neizoliraneZunaj';
    case NeizoliraneCeviVNeizoliraniZunanjiSteni = 'neizoliraneZnotraj';
    case NeizoliraneCeviVZraku = 'neizoliraneVZraku';
    case NeizoliraneCeviVNotranjiSteni = 'neizoliraneVNotranjiSteni';
}


abstract class Razvod {
    public ?ElementRazvoda $horizontalniVod;
    public ?ElementRazvoda $dvizniVod;
    public ?ElementRazvoda $prikljucniVod;

    public ?\StdClass $crpalka;

    public string $idPrenosnika;

    public function __construct($config = null)
    {
        if ($config) {
            $this->parseConfig($config);
        }
    }

    public function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->idPrenosnika = $config->idPrenosnika;

        $this->horizontalniVod = new ElementRazvoda(VrstaRazvodnihCevi::HorizontalniRazvod, $config->ceviHorizontaliVodi);
        $this->dvizniVod = new ElementRazvoda(VrstaRazvodnihCevi::DvizniVod, $config->ceviDvizniVodi);
        $this->prikljucniVod = new ElementRazvoda(VrstaRazvodnihCevi::PrikljucniVod, $config->ceviPrikljucniVodi);

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // OBTOČNA ČRPALKA
        $this->crpalka = $config->crpalka ?? new \StdClass();

        $this->crpalka->regulacija = $config->crpalka->regulacija ?? 'brezRegulacije';
        if (!in_array($this->crpalka->regulacija, array_keys(Configure::read('lookups.crpalke.regulacija')))) {
            throw new \Exception('Regulacija obtočne črpalke za razvod je neveljavna.');
        }
    }

    abstract function dolzinaCevi(VrstaRazvodnihCevi $vrsta, $cona);
    abstract function maksimalnaDolzinaCevi($cona);

    public function toplotneIzgube($prenosnik, $sistem, $cona, $okolje)
    {
        $toplotneIzgube = [];
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = 24 * $stDni;

            // Izračun povprečnih obremenitev podsistemov 
            $betaH = $cona->energijaOgrevanje[$mesec] / ($sistem->standardnaMoc * $stUr);

            // th – mesečne obratovalne ure – čas [h/M] (enačba 43) 
            $steviloUr = $stUr * (($betaH > 0.05) ? 1 : $betaH / 0.05);

            // Qh,in,em - potrebna dovedena toplota v ogrevala [kWh] (enačba 60)
            $potrebnaToplotaOgrevala = $cona->energijaOgrevanje[$mesec] + $sistem->izgubePrenosnikov[$mesec];

            // βh,d,a – povprečna letna obremenitev razvodnega omrežja [-] 
            // enačba (63)
            $betaI = ($sistem->standardnaMoc * $steviloUr) > 0 ? $potrebnaToplotaOgrevala / ($sistem->standardnaMoc * $steviloUr) : 0;

            // θ m - povprečna temperatura ogrevnega medija [°C]
            // enačba (39)
            $srednjaT = $prenosnik->rezim->srednjaTemperatura($sistem);
            $temperaturaRazvoda = ($betaI > 0) ? (($srednjaT - $cona->notranjaTOgrevanje) * pow($betaI, 1 / $prenosnik->exponentOgrevala) + $cona->notranjaTOgrevanje) : $cona->notranjaTOgrevanje;

            $izgubeZnotrajOvoja = ($this->horizontalniVod->toplotneIzgube($this, $cona, true) +
                $this->dvizniVod->toplotneIzgube($this, $cona, true) +
                $this->prikljucniVod->toplotneIzgube($this, $cona, true)) * $steviloUr * ($temperaturaRazvoda - $cona->notranjaTOgrevanje) / 1000;

            $izgubeZunajOvoja = ($this->horizontalniVod->toplotneIzgube($this, $cona, false) +
                $this->dvizniVod->toplotneIzgube($this, $cona, false) +
                $this->prikljucniVod->toplotneIzgube($this, $cona, false)) * $steviloUr * ($temperaturaRazvoda - $cona->zunanjaT) / 1000;


            $toplotneIzgube[$mesec] = $izgubeZnotrajOvoja + $izgubeZunajOvoja;
        }

        return $toplotneIzgube;
    }

    public function potrebnaElektricnaEnergija($prenosnik, $sistem, $cona)
    {
        
        $jeZnanaCrpalka = !empty($this->crpalka->moc);
        $izracunanaMocCrpalke = $this->izracunHidravlicneMoci($this, $prenosnik, $sistem, $cona);
        $this->crpalka->moc = $this->crpalka->moc ?? $izracunanaMocCrpalke;

        // možnosti sta elektrika in toplota
        $this->navlazevanjeZraka = $razvod->navlazevanjeZraka ?? 'elektrika';

        // korekcijski faktor za hidravlično omrežje [-] 
        //      za dvocevni sistem: fsch = 1 
        //      za enocevni sistem: fsch = 8,6⋅m + 0,7 
        //          m – delež masnega pretoka skozi ogrevalo 
        $f_sch = ($razvod->vrsta == 'enocevni') ? 2.3 : 1;

        // korekcijski faktor za hidravlično uravnoteženje [-]
        //      za hidravlično uravnotežene sisteme: 1 
        //      za hidravlično neuravnotežene sisteme: 1,1 
        $f_abgl = empty($razvod->neuravnotezen) ? 1 : 1.1;

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

    public function izracunHidravlicneMoci($razvod, $prenosnik, $sistem, $cona)
    {
        $Lmax = $this->maksimalnaDolzinaCevi($cona);

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



        $deltaTHKLookup = [
            '35/30' => [5, 10],
            '40/30' => [10, 15],
            '55/45' => [10, 15],
        ];

        // ΔθHK – temperaturna razlika pri standardnem temperaturnem režimu ogrevalnega sistema [°C] (enačba 38) 
        // 0 velja za zrak-zrak - oz. če ni definirana vrsta
        $deltaT_HK = 0;
        if (isset($prenosnik->rezim)) {
            $deltaT_HK = $prenosnik->rezim->temperaturnaRazlikaStandardnegaRezima($sistem);
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