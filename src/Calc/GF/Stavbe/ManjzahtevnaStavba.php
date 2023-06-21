<?php
declare(strict_types=1);

namespace App\Calc\GF\Stavbe;

use App\Calc\TSS\TSSVrstaEnergenta;

class ManjzahtevnaStavba extends Stavba
{
    public float $brutoProstornina = 0;
    public float $povrsinaOvoja = 0;
    public float $ogrevanaPovrsina = 0;
    public float $transparentnaPovrsina = 0;

    public float $faktorOblike = 1;
    public float $razmerjeTranspCelota = 1;

    public float $specTransmisijskeIzgube = 0;
    public float $specVentilacijskeIzgube = 0;
    public float $skupnaEnergijaOgrevanje = 0;
    public float $skupnaEnergijaHlajenje = 0;
    public float $skupnaEnergijaTSV = 0;
    public float $skupnaEnergijaNavlazevanje = 0;
    public float $skupnaEnergijaRazvlazevanje = 0;
    public float $skupnaEnergijaRazsvetljava = 0;

    public float $specKoeficientTransmisijskihIzgub = 0;
    public float $specLetnaToplota = 0;
    public float $specLetniHlad = 0;
    public float $specEnergijaTSV = 0;
    public float $specEnergijaNavlazevanje = 0;
    public float $specEnergijaRazvlazevanje = 0;

    public float $dovoljenaSpecLetnaToplota = 25;
    public float $dovoljenSpecKoeficientTransmisijskihIzgub = 25;

    public array $energijaPoEnergentih = [];
    public float $neutezenaDovedenaEnergija = 0;
    public float $utezenaDovedenaEnergija = 0;
    public float $skupnaPrimarnaEnergija = 0;
    public float $neobnovljivaPrimarnaEnergija = 0;
    public float $obnovljivaPrimarnaEnergija = 0;
    public float $izpustCO2 = 0;

    public float $skupnaOddanaElektricnaEnergija = 0;

    public float $letnaUcinkovitostOgrHlaTsv = 0;

    public float $ROVE = 0;
    public float $minROVE = 0;

    public float $specificnaPrimarnaEnergija = 0;
    public float $korigiranaSpecificnaPrimarnaEnergija = 0;

    public float $dovoljenaSpecificnaPrimarnaEnergija = 75;
    public float $dovoljenaKorigiranaSpecificnaPrimarnaEnergija = 75;

    /**
     * tabela 4: 1. korekcijski faktor specifičnega koeficienta transmisijskih toplotnih izgub
     *
     * @var float
     */
    public float $X_Htr = 1;
    /**
     * tabela 4: 2. korekcijski faktor potrebne toplote za ogrevanje stavbe
     *
     * @var float
     */
    public float $X_Hnd = 1;
    /**
     * tabela 4: 3. korekcijski faktor dovoljene potrebne primarne energije za delovanje TSS glede na vrsto stavbe
     *
     * @var float
     */
    public float $X_s = 1;
    /**
     * 20. člen pravilnika
     * mejne vrednosti učinkovite rabe energije v prihodnjem obdobju
     *
     * @var float
     */
    public float $X_OVE = 1;
    /**
     * 20. člen pravilnika
     * mejne vrednosti učinkovite rabe energije v prihodnjem obdobju
     *
     * @var float
     */
    public float $X_p = 1;
    /**
     * tabela 4: 4. kompenzacijski faktor primarne energije, potrebne za ogrevanje stavbe
     *
     * @var float
     */
    public float $Y_Hnd = 1;
    /**
     * tabela 4: 5. kompenzacijski faktor primarne energije
     *
     * @var float
     */
    public float $Y_ROVE = 1;

    /**
     * Analiza stavbe
     *
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public function analiza($okolje)
    {
        $this->brutoProstornina = array_reduce($this->cone, fn($vsota, $cona) => $vsota + $cona->brutoProstornina, 0);
        $this->povrsinaOvoja = array_reduce($this->cone, fn($vsota, $cona) => $vsota + $cona->povrsinaOvoja, 0);
        $this->ogrevanaPovrsina = array_reduce($this->cone, fn($vsota, $cona) => $vsota + $cona->ogrevanaPovrsina, 0);

        foreach ($this->cone as $cona) {
            foreach ($cona->ovoj->transparentneKonstrukcije as $elementOvoja) {
                $this->transparentnaPovrsina += $elementOvoja->povrsina *
                    (1 - $elementOvoja->delezOkvirja) *
                    $elementOvoja->stevilo;
            }
        }

        $this->faktorOblike = round($this->povrsinaOvoja / $this->brutoProstornina, 3);
        $this->razmerjeTranspCelota = $this->transparentnaPovrsina / $this->povrsinaOvoja;

        foreach ($this->cone as $cona) {
            $this->specTransmisijskeIzgube += $cona->specTransmisijskeIzgube;
            $this->specVentilacijskeIzgube += $cona->specVentilacijskeIzgube;
            $this->skupnaEnergijaOgrevanje += $cona->skupnaEnergijaOgrevanje;
            $this->skupnaEnergijaHlajenje += $cona->skupnaEnergijaHlajenje;
            $this->skupnaEnergijaTSV += $cona->skupnaEnergijaTSV;
            $this->skupnaEnergijaNavlazevanje += $cona->skupnaEnergijaNavlazevanje;
            $this->skupnaEnergijaRazvlazevanje += $cona->skupnaEnergijaRazvlazevanje;
            $this->skupnaEnergijaRazsvetljava += $cona->skupnaEnergijaRazsvetljava;
        }

        $this->specKoeficientTransmisijskihIzgub = $this->specTransmisijskeIzgube / $this->povrsinaOvoja;
        $this->specLetnaToplota = $this->skupnaEnergijaOgrevanje / $this->ogrevanaPovrsina;
        $this->specLetniHlad = $this->skupnaEnergijaHlajenje / $this->ogrevanaPovrsina;
        $this->specEnergijaTSV = $this->skupnaEnergijaTSV / $this->ogrevanaPovrsina;
        $this->specEnergijaNavlazevanje = $this->skupnaEnergijaNavlazevanje / $this->ogrevanaPovrsina;
        $this->specEnergijaRazvlazevanje = $this->skupnaEnergijaRazvlazevanje / $this->ogrevanaPovrsina;

        $this->dovoljenaSpecLetnaToplota = 25 * $this->X_Htr;

        if ($this->specLetnaToplota > $this->dovoljenaSpecLetnaToplota) {
            $this->Y_Hnd = 1.2;
        }

        $povprecnaLetnaTemp = $okolje->povprecnaLetnaTemp < 7 ? 7 :
            ($okolje->povprecnaLetnaTemp > 11 ? 11 : $okolje->povprecnaLetnaTemp);

        $faktorOblike = $this->faktorOblike < 0.2 ? 0.2 :
            ($this->faktorOblike > 1.2 ? 1.2 : $this->faktorOblike);

        $this->dovoljenSpecKoeficientTransmisijskihIzgub = 0.25 +
            $povprecnaLetnaTemp / 300 +
            0.04 / $faktorOblike +
            ($this->transparentnaPovrsina / $this->povrsinaOvoja) / 8;
    }

    /**
     * Glavna metoda za analizo TSS
     *
     * @return void
     */
    public function analizaTSS()
    {
        $utezenaDovedenaEnergijaOgrHlaTsv = 0;
        $skupnaDovedenaEnergijaOgrHlaTsv = 0;

        foreach ($this->sistemi as $sistem) {
            $jeOgrevalniSistem = false;
            $podsistemi = [];
            if (isset($sistem->energijaPoEnergentih->tsv)) {
                $podsistemi[] = 'tsv';
                $jeOgrevalniSistem = true;
                $skupnaDovedenaEnergijaOgrHlaTsv += $this->skupnaEnergijaTSV;
            }
            if (isset($sistem->energijaPoEnergentih->ogrevanje)) {
                $podsistemi[] = 'ogrevanje';
                $jeOgrevalniSistem = true;
                $skupnaDovedenaEnergijaOgrHlaTsv += $this->skupnaEnergijaOgrevanje;
            }
            if (isset($sistem->energijaPoEnergentih->hlajenje)) {
                $podsistemi[] = 'hlajenje';
                $jeOgrevalniSistem = true;
                $skupnaDovedenaEnergijaOgrHlaTsv += $this->skupnaEnergijaHlajenje;
            }

            $sistemEnergijaPoEnergentih = (array)$sistem->energijaPoEnergentih;
            if (count($podsistemi) == 0) {
                $podsistemi[] = 'default';
                $sistemEnergijaPoEnergentih = ['default' => $sistemEnergijaPoEnergentih];
            }

            foreach ($podsistemi as $podsistem) {
                $this->energijaPoEnergentih += (array)$sistemEnergijaPoEnergentih[$podsistem];
                foreach ((array)$sistemEnergijaPoEnergentih[$podsistem] as $energent => $energija) {
                    // za siseme, ki ne uporabljajo elektricne energije ampak jo proizvajajo
                    if (!empty($sistem->potrebnaEnergija) || !empty($sistem->potrebnaElektricnaEnergija)) {
                        $this->neutezenaDovedenaEnergija += $energija;

                        $this->utezenaDovedenaEnergija +=
                            $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('tot');
                    }

                    if ($jeOgrevalniSistem) {
                        $utezenaDovedenaEnergijaOgrHlaTsv +=
                            $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('tot');
                    }

                    $this->skupnaPrimarnaEnergija +=
                            $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('tot');

                    $this->neobnovljivaPrimarnaEnergija +=
                        $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('nren');

                    $this->obnovljivaPrimarnaEnergija +=
                        $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('ren');

                    $this->izpustCO2 +=
                        $energija * TSSVrstaEnergenta::from($energent)->faktorIzpustaCO2();
                }
            }

            // fotovoltaika pri oddaji električne energije v omrežje
            if (isset($sistem->oddanaElektricnaEnergija)) {
                $this->skupnaPrimarnaEnergija -= array_sum($sistem->oddanaElektricnaEnergija) *
                    TSSVrstaEnergenta::Elektrika->utezniFaktor('tot');

                $this->skupnaOddanaElektricnaEnergija += array_sum($sistem->oddanaElektricnaEnergija);
            }
        }

        $this->letnaUcinkovitostOgrHlaTsv = $skupnaDovedenaEnergijaOgrHlaTsv / $utezenaDovedenaEnergijaOgrHlaTsv;

        $this->ROVE = $this->obnovljivaPrimarnaEnergija / $this->skupnaPrimarnaEnergija * 100;
        $this->minROVE = 50 * $this->X_OVE;

        if ($this->ROVE < $this->minROVE) {
            $this->Y_ROVE = 1.2;
        }
        if ($this->ROVE > $this->minROVE) {
            // TODO: uporablja se do leta 2026
            $this->Y_ROVE = 0.8;
        }

        $this->specificnaPrimarnaEnergija = $this->skupnaPrimarnaEnergija / $this->ogrevanaPovrsina;
        $this->korigiranaSpecificnaPrimarnaEnergija = $this->specificnaPrimarnaEnergija * $this->Y_Hnd * $this->Y_ROVE;
        $this->dovoljenaKorigiranaSpecificnaPrimarnaEnergija = 75 * $this->X_p * $this->X_s;
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $stavba = parent::export();

        $reflect = new \ReflectionClass(ManjzahtevnaStavba::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            $stavba->{$prop->getName()} = $prop->getValue($this);
        }

        return $stavba;
        /*
        $stavba->brutoProstornina = $this->brutoProstornina;
        $stavba->povrsinaOvoja = $this->povrsinaOvoja;
        $stavba->ogrevanaPovrsina = $this->ogrevanaPovrsina;
        $stavba->transparentnaPovrsina = $this->transparentnaPovrsina;

        $stavba->faktorOblike = $this->faktorOblike;
        $stavba->razmerjeTranspCelota = $this->razmerjeTranspCelota;

        $stavba->specTransmisijskeIzgube = $this->specTransmisijskeIzgube;
        $stavba->specVentilacijskeIzgube = $this->specVentilacijskeIzgube;
        $stavba->skupnaEnergijaOgrevanje = $this->skupnaEnergijaOgrevanje;
        $stavba->skupnaEnergijaHlajenje = $this->skupnaEnergijaHlajenje;
        $stavba->skupnaEnergijaTSV = $this->skupnaEnergijaTSV;
        $stavba->skupnaEnergijaNavlazevanje = $this->skupnaEnergijaNavlazevanje;
        $stavba->skupnaEnergijaRazvlazevanje = $this->skupnaEnergijaRazvlazevanje;
        $stavba->skupnaPotrebaRazsvetljava = $this->skupnaPotrebaRazsvetljava;

        $stavba->specKoeficientTransmisijskihIzgub = $this->specKoeficientTransmisijskihIzgub;
        $stavba->specLetnaToplota = $this->specLetnaToplota;
        $stavba->specLetniHlad = $this->specLetniHlad;
        $stavba->specEnergijaTSV = $this->specEnergijaTSV;
        $stavba->specEnergijaNavlazevanje = $this->specEnergijaNavlazevanje;
        $stavba->specEnergijaRazvlazevanje = $this->specEnergijaRazvlazevanje;

        $stavba->dovoljenaSpecLetnaToplota = $this->dovoljenaSpecLetnaToplota;
        $stavba->dovoljenSpecKoeficientTransmisijskihIzgub = $this->dovoljenSpecKoeficientTransmisijskihIzgub;

        $stavba->energijaPoEnergentih = $this->energijaPoEnergentih;
        $stavba->neutezenaDovedenaEnergija = $this->neutezenaDovedenaEnergija;
        $stavba->utezenaDovedenaEnergija = $this->utezenaDovedenaEnergija;
        $stavba->skupnaPrimarnaEnergija = $this->skupnaPrimarnaEnergija;
        $stavba->neobnovljivaPrimarnaEnergija = $this->neobnovljivaPrimarnaEnergija;
        $stavba->obnovljivaPrimarnaEnergija = $this->obnovljivaPrimarnaEnergija;
        $stavba->izpustCO2 = $this->izpustCO2;

        $stavba->skupnaOddanaElektricnaEnergija = $this->skupnaOddanaElektricnaEnergija;

        $stavba->letnaUcinkovitostOgrHlaTsv = $this->letnaUcinkovitostOgrHlaTsv;

        $stavba->ROVE = $this->ROVE;
        $stavba->minROVE = $this->minROVE;

        $stavba->specificnaPrimarnaEnergija = $this->specificnaPrimarnaEnergija;
        $stavba->korigiranaSpecificnaPrimarnaEnergija = $this->korigiranaSpecificnaPrimarnaEnergija;

        $stavba->dovoljenaSpecificnaPrimarnaEnergija = $this->dovoljenaSpecificnaPrimarnaEnergija;
        $stavba->dovoljenaKorigiranaSpecificnaPrimarnaEnergija = $this->dovoljenaKorigiranaSpecificnaPrimarnaEnergija;

        $stavba->X_Htr = $this->X_Htr;
        $stavba->X_Hnd = $this->X_Hnd;
        $stavba->X_s = $this->X_s;
        $stavba->X_OVE = $this->X_OVE;
        $stavba->X_p = $this->X_p;
        $stavba->Y_Hnd = $this->Y_Hnd;
        $stavba->Y_ROVE = $this->Y_ROVE;*/
    }
}
