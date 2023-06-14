<?php
declare(strict_types=1);

namespace App\Lib;

use App\Calc\TSS\TSSVrstaEnergenta;

class CalcStavba
{
    /**
     * Glavna metoda za analizo stavbe
     *
     * @param array $cone Seznam con
     * @param \stdClass $okolje Parametri okolja
     * @param \stdClass $splosniPodatki Splošni podatki o stavbi
     * @return \stdClass
     */
    public static function analiza($cone, $okolje, $splosniPodatki)
    {
        $stavba = new \stdClass();

        $stavba->brutoProstornina = array_reduce($cone, fn($vsota, $cona) => $vsota + $cona->brutoProstornina, 0);
        $stavba->povrsinaOvoja = array_reduce($cone, fn($vsota, $cona) => $vsota + $cona->povrsinaOvoja, 0);
        $stavba->ogrevanaPovrsina = array_reduce($cone, fn($vsota, $cona) => $vsota + $cona->ogrevanaPovrsina, 0);

        $stavba->transparentnaPovrsina = 0;
        foreach ($cone as $cona) {
            foreach ($cona->ovoj->transparentneKonstrukcije as $elementOvoja) {
                $stavba->transparentnaPovrsina += $elementOvoja->povrsina *
                    (1 - $elementOvoja->delezOkvirja) *
                    $elementOvoja->stevilo;
            }
        }

        // tabela 4:
        // 1. korekcijski faktor specifičnega koeficienta transmisijskih toplotnih izgub
        $stavba->X_Htr = 1;
        // 2. korekcijski faktor potrebne toplote za ogrevanje stavbe
        $stavba->X_Hnd = 1;
        // 3. korekcijski faktor dovoljene potrebne primarne energije za delovanje TSS glede na vrsto stavbe
        $stavba->X_s = 1;
        // 4. kompenzacijski faktor primarne energije, potrebne za ogrevanje stavbe
        $stavba->Y_Hnd = 1;
        // 5. kompenzacijski faktor primarne energije
        $stavba->Y_ROVE = 1;

        // 20. člen pravilnika
        // mejne vrednosti učinkovite rabe energije v prihodnjem obdobju
        $stavba->X_OVE = 1;
        $stavba->X_p = 1;

        $stavba->faktorOblike = round($stavba->povrsinaOvoja / $stavba->brutoProstornina, 3);
        $stavba->razmerjeTranspCelota = $stavba->transparentnaPovrsina / $stavba->povrsinaOvoja;

        $stavba->specTransmisijskeIzgube = 0;
        $stavba->specVentilacijskeIzgube = 0;
        $stavba->skupnaEnergijaOgrevanje = 0;
        $stavba->skupnaEnergijaHlajenje = 0;
        $stavba->skupnaEnergijaTSV = 0;
        $stavba->skupnaEnergijaNavlazevanje = 0;
        $stavba->skupnaEnergijaRazvlazevanje = 0;
        $stavba->skupnaPotrebaRazsvetljava = 0;
        foreach ($cone as $cona) {
            $stavba->specTransmisijskeIzgube += $cona->specTransmisijskeIzgube;
            $stavba->specVentilacijskeIzgube += $cona->specVentilacijskeIzgube;
            $stavba->skupnaEnergijaOgrevanje += $cona->skupnaEnergijaOgrevanje;
            $stavba->skupnaEnergijaHlajenje += $cona->skupnaEnergijaHlajenje;
            $stavba->skupnaEnergijaTSV += $cona->skupnaEnergijaTSV;
            $stavba->skupnaEnergijaNavlazevanje += $cona->skupnaEnergijaNavlazevanje;
            $stavba->skupnaEnergijaRazvlazevanje += $cona->skupnaEnergijaRazvlazevanje;
            $stavba->skupnaPotrebaRazsvetljava += $cona->skupnaPotrebaRazsvetljava;
        }

        $stavba->specKoeficientTransmisijskihIzgub = $stavba->specTransmisijskeIzgube / $stavba->povrsinaOvoja;
        $stavba->specLetnaToplota = $stavba->skupnaEnergijaOgrevanje / $stavba->ogrevanaPovrsina;
        $stavba->specLetniHlad = $stavba->skupnaEnergijaHlajenje / $stavba->ogrevanaPovrsina;
        $stavba->specenergijaTSV = $stavba->skupnaEnergijaTSV / $stavba->ogrevanaPovrsina;

        $stavba->specEnergijaNavlazevanje = $stavba->skupnaEnergijaNavlazevanje / $stavba->ogrevanaPovrsina;
        $stavba->specEnergijaRazvlazevanje = $stavba->skupnaEnergijaRazvlazevanje / $stavba->ogrevanaPovrsina;

        $stavba->dovoljenaSpecLetnaToplota = 25 * $stavba->X_Htr;

        if ($stavba->specLetnaToplota > $stavba->dovoljenaSpecLetnaToplota) {
            $stavba->Y_Hnd = 1.2;
        }

        $povprecnaLetnaTemp = $okolje->povprecnaLetnaTemp < 7 ? 7 :
            ($okolje->povprecnaLetnaTemp > 11 ? 11 : $okolje->povprecnaLetnaTemp);
        $faktorOblike = $stavba->faktorOblike < 0.2 ? 0.2 :
            ($stavba->faktorOblike > 1.2 ? 1.2 : $stavba->faktorOblike);
        $stavba->dovoljenSpecKoeficientTransmisijskihIzgub = 0.25 +
            $povprecnaLetnaTemp / 300 +
            0.04 / $faktorOblike +
            ($stavba->transparentnaPovrsina / $stavba->povrsinaOvoja) / 8;

        return $stavba;
    }

    /**
     * Glavna metoda za analizo TSS
     *
     * @param \stdClass $stavba Podatki stavbe
     * @param array $sistemi Podatki sistemov
     * @return \stdClass
     */
    public static function analizaTSS($stavba, $sistemi)
    {
        $stavba->energijaPoEnergentih = [];
        $stavba->neutezenaDovedenaEnergija = 0;
        $stavba->utezenaDovedenaEnergija = 0;
        $stavba->skupnaPrimarnaEnergija = 0;
        $stavba->neobnovljivaPrimarnaEnergija = 0;
        $stavba->obnovljivaPrimarnaEnergija = 0;
        $stavba->izpustCO2 = 0;

        $stavba->skupnaOddanaElektricnaEnergija = 0;

        $utezenaDovedenaEnergijaOgrHlaTsv = 0;
        $skupnaDovedenaEnergijaOgrHlaTsv = 0;

        foreach ($sistemi as $sistem) {
            $jeOgrevalniSistem = false;
            $podsistemi = [];
            if (isset($sistem->energijaPoEnergentih->tsv)) {
                $podsistemi[] = 'tsv';
                $jeOgrevalniSistem = true;
                $skupnaDovedenaEnergijaOgrHlaTsv += $stavba->skupnaEnergijaTSV;
            }
            if (isset($sistem->energijaPoEnergentih->ogrevanje)) {
                $podsistemi[] = 'ogrevanje';
                $jeOgrevalniSistem = true;
                $skupnaDovedenaEnergijaOgrHlaTsv += $stavba->skupnaEnergijaOgrevanje;
            }
            if (isset($sistem->energijaPoEnergentih->hlajenje)) {
                $podsistemi[] = 'hlajenje';
                $jeOgrevalniSistem = true;
                $skupnaDovedenaEnergijaOgrHlaTsv += $stavba->skupnaEnergijaHlajenje;
            }

            $sistemEnergijaPoEnergentih = (array)$sistem->energijaPoEnergentih;
            if (count($podsistemi) == 0) {
                $podsistemi[] = 'default';
                $sistemEnergijaPoEnergentih = ['default' => $sistemEnergijaPoEnergentih];
            }

            foreach ($podsistemi as $podsistem) {
                $stavba->energijaPoEnergentih += (array)$sistemEnergijaPoEnergentih[$podsistem];
                foreach ((array)$sistemEnergijaPoEnergentih[$podsistem] as $energent => $energija) {
                    // za siseme, ki ne uporabljajo elektricne energije ampak jo proizvajajo
                    if (!empty($sistem->potrebnaEnergija) || !empty($sistem->potrebnaElektricnaEnergija)) {
                        $stavba->neutezenaDovedenaEnergija += $energija;

                        $stavba->utezenaDovedenaEnergija +=
                            $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('tot');
                    }

                    if ($jeOgrevalniSistem) {
                        $utezenaDovedenaEnergijaOgrHlaTsv +=
                            $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('tot');
                    }

                    $stavba->skupnaPrimarnaEnergija +=
                            $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('tot');

                    $stavba->neobnovljivaPrimarnaEnergija +=
                        $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('nren');

                    $stavba->obnovljivaPrimarnaEnergija +=
                        $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('ren');

                    $stavba->izpustCO2 +=
                        $energija * TSSVrstaEnergenta::from($energent)->faktorIzpustaCO2();
                }
            }

            // fotovoltaika pri oddaji električne energije v omrežje
            if (isset($sistem->oddanaElektricnaEnergija)) {
                $stavba->skupnaPrimarnaEnergija -= array_sum($sistem->oddanaElektricnaEnergija) *
                    TSSVrstaEnergenta::Elektrika->utezniFaktor('tot');

                $stavba->skupnaOddanaElektricnaEnergija += array_sum($sistem->oddanaElektricnaEnergija);
            }
        }

        $stavba->letnaUcinkovitostOgrHlaTsv = $skupnaDovedenaEnergijaOgrHlaTsv / $utezenaDovedenaEnergijaOgrHlaTsv;

        $stavba->ROVE = $stavba->obnovljivaPrimarnaEnergija / $stavba->skupnaPrimarnaEnergija * 100;
        $stavba->minROVE = 50 * $stavba->X_OVE;

        if ($stavba->ROVE < $stavba->minROVE) {
            $stavba->Y_ROVE = 1.2;
        }
        if ($stavba->ROVE > $stavba->minROVE) {
            // TODO: uporablja se do leta 2026
            $stavba->Y_ROVE = 0.8;
        }

        $stavba->specificnaPrimarnaEnergija = $stavba->skupnaPrimarnaEnergija / $stavba->ogrevanaPovrsina;
        $stavba->korigiranaSpecificnaPrimarnaEnergija =
            $stavba->specificnaPrimarnaEnergija * $stavba->Y_Hnd * $stavba->Y_ROVE;

        $stavba->dovoljenaKorigiranaSpecificnaPrimarnaEnergija = 75 * $stavba->X_p * $stavba->X_s;

        return $stavba;
    }
}
