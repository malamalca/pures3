<?php
declare(strict_types=1);

namespace App\Lib;

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

        $stavba->brutoProstornina = array_reduce($cone, function ($vsota, $cona) {
            return $vsota += $cona->brutoProstornina;
        }, 0);
        $stavba->povrsinaOvoja = array_reduce($cone, function ($vsota, $cona) {

            return $vsota += $cona->povrsinaOvoja;
        }, 0);
        $stavba->ogrevanaPovrsina = array_reduce($cone, function ($vsota, $cona) {

            return $vsota += $cona->ogrevanaPovrsina;
        }, 0);
        $stavba->transparentnaPovrsina = 0;
        foreach ($cone as $cona) {
            foreach ($cona->ovoj->transparentneKonstrukcije as $elementOvoja) {
                $stavba->transparentnaPovrsina += $elementOvoja->povrsina *
                    (1 - $elementOvoja->delezOkvirja) *
                    $elementOvoja->stevilo;
            }
        }

        $stavba->X_Htr = 1;
        $stavba->X_Hnd = 1;
        $stavba->Y_Hnd = 1;

        $stavba->faktorOblike = round($stavba->povrsinaOvoja / $stavba->brutoProstornina, 3);
        $stavba->razmerjeTranspCelota = $stavba->transparentnaPovrsina / $stavba->povrsinaOvoja;

        $stavba->specTransmisijskeIzgube = 0;
        $stavba->specVentilacijskeIzgube = 0;
        $stavba->skupnaEnergijaOgrevanje = 0;
        $stavba->skupnaEnergijaHlajenje = 0;
        $stavba->skupnaPotrebaTSV = 0;
        $stavba->skupnaEnergijaNavlazevanje = 0;
        $stavba->skupnaEnergijaRazvlazevanje = 0;
        $stavba->skupnaPotrebaRazsvetljava = 0;
        foreach ($cone as $cona) {
            $stavba->specTransmisijskeIzgube += $cona->specTransmisijskeIzgube;
            $stavba->specVentilacijskeIzgube += $cona->specVentilacijskeIzgube;
            $stavba->skupnaEnergijaOgrevanje += $cona->skupnaEnergijaOgrevanje;
            $stavba->skupnaEnergijaHlajenje += $cona->skupnaEnergijaHlajenje;
            $stavba->skupnaPotrebaTSV += $cona->skupnaPotrebaTSV;
            $stavba->skupnaEnergijaNavlazevanje += $cona->skupnaEnergijaNavlazevanje;
            $stavba->skupnaEnergijaRazvlazevanje += $cona->skupnaEnergijaRazvlazevanje;
            $stavba->skupnaPotrebaRazsvetljava += $cona->skupnaPotrebaRazsvetljava;
        }

        $stavba->specKoeficientTransmisijskihIzgub = $stavba->specTransmisijskeIzgube / $stavba->povrsinaOvoja;
        $stavba->specLetnaToplota = $stavba->skupnaEnergijaOgrevanje / $stavba->ogrevanaPovrsina;
        $stavba->specLetniHlad = $stavba->skupnaEnergijaHlajenje / $stavba->ogrevanaPovrsina;
        $stavba->specPotrebaTSV = $stavba->skupnaPotrebaTSV / $stavba->ogrevanaPovrsina;

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
}
