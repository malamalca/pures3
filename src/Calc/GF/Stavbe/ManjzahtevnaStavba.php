<?php
declare(strict_types=1);

namespace App\Calc\GF\Stavbe;

use App\Calc\GF\Stavbe\Izbire\VrstaGradnje;
use App\Calc\GF\TSS\TSSVrstaEnergenta;

class ManjzahtevnaStavba extends Stavba
{
    public float $brutoProstornina = 0;
    public float $netoProstornina = 0;
    public float $povrsinaOvoja = 0;
    public float $ogrevanaPovrsina = 0;
    public float $transparentnaPovrsina = 0;

    public float $faktorOblike = 1;
    public float $razmerjeTranspCelota = 1;
    public float $povprecnaLetnaTemp = 0;

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

    public float $neutezenaDovedenaEnergija = 0;
    public float $utezenaDovedenaEnergija = 0;
    public float $skupnaPrimarnaEnergija = 0;
    public float $neobnovljivaPrimarnaEnergija = 0;
    public float $obnovljivaPrimarnaEnergija = 0;
    public float $izpustCO2 = 0;

    public float $skupnaOddanaElektricnaEnergija = 0;
    public float $skupnaProizvedenaPorabljenaElektricnaEnergija = 0;
    public float $skupnaProizvedenaElektricnaEnergija = 0;

    public float $skupnaOddanaToplota = 0;

    public float $faktorUjemanja = 0;

    public float $ROVE = 0;
    public float $minROVE = 0;

    public float $specificnaPrimarnaEnergija = 0;
    public float $korigiranaSpecificnaPrimarnaEnergija = 0;

    public float $dovoljenaSpecificnaPrimarnaEnergija = 75;
    public float $dovoljenaKorigiranaSpecificnaPrimarnaEnergija = 75;

    public array $energijaTSSPoEnergentih = [];

    /**
     * Analiza stavbe
     *
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public function analiza($okolje)
    {
        $this->brutoProstornina =
            (float)array_reduce($this->cone, fn($vsota, $cona) => $vsota + $cona->brutoProstornina, 0);
        $this->povrsinaOvoja =
            (float)array_reduce($this->cone, fn($vsota, $cona) => $vsota + $cona->povrsinaOvoja, 0);
        $this->ogrevanaPovrsina =
            (float)array_reduce($this->cone, fn($vsota, $cona) => $vsota + $cona->ogrevanaPovrsina, 0);
        $this->netoProstornina =
            (float)array_reduce($this->cone, fn($vsota, $cona) => $vsota + $cona->netoProstornina, 0);

        if ($this->povrsinaOvoja == 0.00 || $this->ogrevanaPovrsina == 0.00) {
            return;
        }

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

        $this->dovoljenaSpecLetnaToplota = 25 * $this->X_Hnd();

        $this->povprecnaLetnaTemp = $okolje->povprecnaLetnaTemp < 7 ? 7 :
            ($okolje->povprecnaLetnaTemp > 11 ? 11 : $okolje->povprecnaLetnaTemp);

        $faktorOblike = $this->faktorOblike < 0.2 ? 0.2 :
            ($this->faktorOblike > 1.2 ? 1.2 : $this->faktorOblike);

        $this->dovoljenSpecKoeficientTransmisijskihIzgub = 0.25 +
            $this->povprecnaLetnaTemp / 300 +
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
        $skupnaPotrebnaElektricnaEnergijaOgrHlaTsv = 0;
        $faktorjiUjemanja = [];

        foreach ($this->sistemi as $sistem) {
            if (isset($sistem->tsv)) {
                $skupnaDovedenaEnergijaOgrHlaTsv += $this->skupnaEnergijaTSV;
            }
            if (isset($sistem->ogrevanje)) {
                $skupnaDovedenaEnergijaOgrHlaTsv += $this->skupnaEnergijaOgrevanje;
            }
            if (isset($sistem->hlajenje)) {
                $skupnaDovedenaEnergijaOgrHlaTsv += $this->skupnaEnergijaHlajenje;
            }

            if (!empty($sistem->jeOgrevalniSistem)) {
                if (!empty($sistem->potrebnaElektricnaEnergija)) {
                    $skupnaPotrebnaElektricnaEnergijaOgrHlaTsv += array_sum($sistem->potrebnaElektricnaEnergija);
                }
            }

            foreach ((array)$sistem->energijaPoEnergentih as $energent => $energija) {
                if ($energija > 0) {
                    // če je energija manjška kot nič pomeni, da je proizvedena in se je porabila v stavbi
                    $this->neutezenaDovedenaEnergija += $energija;
                    $this->utezenaDovedenaEnergija +=
                        $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('tsg');

                    $tss = $sistem->tss;
                    if ($tss == 'oht') {
                        if (isset($sistem->ogrevanje)) {
                            foreach ((array)$sistem->ogrevanje->energijaPoEnergentih as $energentOgr => $energijaOgr) {
                                $this->energijaTSSPoEnergentih['ogrevanje'][$energentOgr] =
                                    ($this->energijaTSSPoEnergentih['ogrevanje'][$energentOgr] ?? 0) + $energijaOgr;
                            }
                        }
                        if (isset($sistem->hlajenje)) {
                            foreach ((array)$sistem->hlajenje->energijaPoEnergentih as $energentHl => $energijaHl) {
                                $this->energijaTSSPoEnergentih['hlajenje'][$energentHl] =
                                    ($this->energijaTSSPoEnergentih['hlajenje'][$energentHl] ?? 0) + $energijaHl;
                            }
                        }
                        if (isset($sistem->tsv)) {
                            foreach ((array)$sistem->tsv->energijaPoEnergentih as $energentTsv => $energijaTsv) {
                                $this->energijaTSSPoEnergentih['tsv'][$energentTsv] =
                                    ($this->energijaTSSPoEnergentih['tsv'][$energentTsv] ?? 0) + $energijaTsv;
                            }
                        }
                    } else {
                        $this->energijaTSSPoEnergentih[$tss][$energent] =
                            ($this->energijaTSSPoEnergentih[$tss][$energent] ?? 0) + $energija;
                    }
                } else {
                    if ($energent == TSSVrstaEnergenta::Elektrika->value) {
                        $this->skupnaProizvedenaPorabljenaElektricnaEnergija += $energija * -1;
                    }
                }

                if (!empty($sistem->jeOgrevalniSistem)) {
                    $utezenaDovedenaEnergijaOgrHlaTsv +=
                        $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('tot');
                }

                $this->neobnovljivaPrimarnaEnergija +=
                    $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('nren');

                $this->obnovljivaPrimarnaEnergija +=
                    $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('ren');

                $this->skupnaPrimarnaEnergija +=
                    $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('tot');

                $this->izpustCO2 +=
                    $energija * TSSVrstaEnergenta::from($energent)->faktorIzpustaCO2();
            }

            if (isset($sistem->proizvedenaEnergijaPoEnergentih)) {
                foreach ((array)$sistem->proizvedenaEnergijaPoEnergentih as $energent => $energija) {
                    $this->neobnovljivaPrimarnaEnergija +=
                        $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('nren');

                    $this->obnovljivaPrimarnaEnergija +=
                        $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('ren');

                    $this->skupnaPrimarnaEnergija +=
                        $energija * TSSVrstaEnergenta::from($energent)->utezniFaktor('tot');

                    $this->izpustCO2 +=
                        $energija * TSSVrstaEnergenta::from($energent)->faktorIzpustaCO2();

                    $this->skupnaProizvedenaElektricnaEnergija += $energija;
                }
            }

            if (isset($sistem->oddanaEnergijaPoEnergentih)) {
                foreach ((array)$sistem->oddanaEnergijaPoEnergentih as $energent => $energija) {
                    $this->skupnaPrimarnaEnergija -=
                        $energija * $this->k_exp('elektrika') * TSSVrstaEnergenta::from($energent)->utezniFaktor('tot');

                    $this->izpustCO2 -= $energija * TSSVrstaEnergenta::from($energent)->faktorIzpustaCO2();

                    if ($energent == TSSVrstaEnergenta::Elektrika->value) {
                        $this->skupnaOddanaElektricnaEnergija += $energija;
                    }
                }
            }

            if (isset($sistem->faktorUjemanja)) {
                $faktorjiUjemanja[] = array_sum($sistem->faktorUjemanja) / count($sistem->faktorUjemanja);
            }
        }

        if (count($faktorjiUjemanja) > 0) {
            $this->faktorUjemanja = array_sum($faktorjiUjemanja) / count($faktorjiUjemanja);
        }

        if ($utezenaDovedenaEnergijaOgrHlaTsv == 0.0 || $this->skupnaPrimarnaEnergija == 0.0) {
            return;
        }

        if ($this->skupnaPrimarnaEnergija < 0) {
            // če je fotovoltaika je skupnaPrimarna energija LAHKO manjša kot nič
            //$this->ROVE = ($this->obnovljivaPrimarnaEnergija - $this->skupnaPrimarnaEnergija) / (-$this->skupnaPrimarnaEnergija) * 100;
            $this->ROVE = 100;
        } else {
            $this->ROVE = $this->obnovljivaPrimarnaEnergija / $this->skupnaPrimarnaEnergija * 100;
        }
        if ($this->ROVE > 100) {
            $this->ROVE = 100;
        }

        $this->minROVE = 50 * $this->X_OVE();

        $this->specificnaPrimarnaEnergija =
            $this->skupnaPrimarnaEnergija / $this->ogrevanaPovrsina;
        $this->korigiranaSpecificnaPrimarnaEnergija =
            $this->specificnaPrimarnaEnergija * $this->Y_Hnd() * $this->Y_ROVE();
        $this->dovoljenaKorigiranaSpecificnaPrimarnaEnergija =
            75 * $this->X_p() * $this->X_s();
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

        $stavba->X_Htr = $this->X_Htr();
        $stavba->X_Hnd = $this->X_Hnd();
        $stavba->X_s = $this->X_s();
        $stavba->Y_Hnd = $this->Y_Hnd();
        $stavba->Y_ROVE = $this->Y_ROVE();

        $stavba->X_OVE = $this->X_OVE();
        $stavba->X_p = $this->X_p();

        $stavba->k_exp = $this->k_exp('elektrika');

        return $stavba;
    }

    /**
     * tabela 4: 1. korekcijski faktor specifičnega koeficienta transmisijskih toplotnih izgub
     *
     * @return float
     */
    // phpcs:ignore
    public function X_Htr()
    {
        $ret = 1;
        if ($this->tip == VrstaGradnje::CelovitaObnova) {
            $ret = 1.2;
        } elseif ($this->javna) {
            $ret = 0.9;
        }

        return $ret;
    }

    /**
     * tabela 4: 2. korekcijski faktor potrebne toplote za ogrevanje stavbe
     *
     * @return float
     */
    // phpcs:ignore
    public function X_Hnd()
    {
        if ($this->tip == VrstaGradnje::CelovitaObnova) {
            if ($this->javna) {
                $ret = 1.25;
            } else {
                $ret = 1.4;
            }
        } else {
            if ($this->javna) {
                $ret = 0.9;
            } else {
                $ret = 1.0;
            }
        }

        return $ret;
    }

    /**
     * tabela 4: 3. korekcijski faktor dovoljene potrebne primarne energije za delovanje TSS glede na vrsto stavbe
     *
     * @return float
     */
    // phpcs:ignore
    public function X_s()
    {
        $ret = 1;
        if ($this->tip == VrstaGradnje::CelovitaObnova) {
            $ret = 1.2;
        } elseif ($this->javna) {
            $ret = 0.9;
        }

        return $ret;
    }

    /**
     * tabela 4: 4. kompenzacijski faktor primarne energije, potrebne za ogrevanje stavbe
     *
     * @return float
     */
    // phpcs:ignore
    public function Y_Hnd()
    {
        if ($this->tip == VrstaGradnje::CelovitaObnova) {
            if ($this->specLetnaToplota > $this->dovoljenaSpecLetnaToplota) {
                $ret = 1.2;
            } else {
                $ret = 1.0;
            }
        } else {
            // nove
            if ($this->specLetnaToplota > $this->dovoljenaSpecLetnaToplota) {
                $ret = 1.2;
            } else {
                $ret = 1.0;
            }
        }

        return $ret;
    }

    /**
     * tabela 4: 5. kompenzacijski faktor primarne energije
     *
     * @return float
     */
    // phpcs:ignore
    public function Y_ROVE()
    {
        $ret = 1;
        if ($this->ROVE < $this->minROVE) {
            $ret = 1.2;
        }
        if ($this->ROVE > 50 * $this->X_OVE(2026)) {
            // TODO: uporablja se do leta 2026
            $ret = 0.8;
        }

        return $ret;
    }

    /**
     * 20. člen pravilnika
     * mejne vrednosti učinkovite rabe energije v prihodnjem obdobju
     *
     * @param int $year Current year
     * @return float
     */
    // phpcs:ignore
    public function X_OVE($year = 2023)
    {
        if ($this->javna) {
            if ($year > 2025) {
                $ret = 1.44;
            } else {
                $ret = 1.1;
            }
        } else {
            if ($year > 2025) {
                $ret = 1.3;
            } else {
                $ret = 1.0;
            }
        }

        return $ret;
    }

    /**
     * 20. člen pravilnika
     * mejne vrednosti učinkovite rabe energije v prihodnjem obdobju
     *
     * @param int $year Current year
     * @return float
     */
    // phpcs:ignore
    public function X_p($year = 2023)
    {
        if ($this->javna) {
            if ($year > 2025) {
                $ret = 0.72;
            } else {
                $ret = 0.9;
            }
        } else {
            if ($year > 2025) {
                $ret = 0.8;
            } else {
                $ret = 1.0;
            }
        }

        return $ret;
    }

    /**
     *  Kontrolni faktor na stavbi proizvedenega in oddanega energenta
     *
     * @param string $vrsta 'toplota', 'elektrika', 'elektrikaSHranilnikom'
     * @param int $year Current year
     * @return float
     */
    // phpcs:ignore
    public function k_exp($vrsta, $year = 2023)
    {
        if ($vrsta == 'elektrika') {
            if ($year > 2025) {
                $ret = 0.8;
            } else {
                $ret = 1;
            }
        } else {
            $ret = 1;
        }

        return $ret;
    }
}
