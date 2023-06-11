<?php
declare(strict_types=1);

namespace App\Lib;

use App\Core\Log;

class CalcCone
{
    /**
     * Glavna funkcija za analizo cone
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $netransparentneKonstrukcije Seznam netransparentnih konstrukcij
     * @param array $transparentneKonstrukcije Seznam transparentnih konstrukcij
     * @return \stdClass
     */
    public static function analizaCone($cona, $okolje, $netransparentneKonstrukcije, $transparentneKonstrukcije)
    {
        $cona->deltaPsi = $cona->deltaPsi ?? 0;

        // izračunaj delto temperature med notranjostjo in zuanjim zrakom
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $cona->deltaTOgrevanje[$mesec] = $cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec];
            $cona->deltaTHlajenje[$mesec] = $cona->notranjaTHlajenje - $okolje->zunanjaT[$mesec];

            $cona->transIzgubeOgrevanje[$mesec] = 0;
            $cona->transIzgubeHlajenje[$mesec] = 0;

            $cona->solarniDobitkiOgrevanje[$mesec] = 0;
            $cona->solarniDobitkiHlajenje[$mesec] = 0;

            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            // notranji dobitki
            $cona->notranjiViriOgrevanje[$mesec] = $cona->ogrevanaPovrsina *
                $cona->notranjiViri->ogrevanje * $stDni * 24 / 1000;
            $cona->notranjiViriHlajenje[$mesec] = $cona->ogrevanaPovrsina *
                $cona->notranjiViri->hlajenje * $stDni * 24 / 1000;
        }

        CalcOvojNetransparenten::analiza($cona, $okolje, $netransparentneKonstrukcije);

        CalcOvojTransparenten::analiza($cona, $okolje, $transparentneKonstrukcije);

        self::izracunVentilacijskihIzgub($cona, $okolje);

        self::izracunFaktorjaIzkoristka($cona, $okolje);

        self::izracunEnergijeOgrevanjeHlajanje($cona, $okolje);

        self::izracunTSV($cona, $okolje);

        self::izracunRazsvetljave($cona, $okolje);

        self::izracunNavlazevanje($cona, $okolje);

        // končni izračuni
        $skupni_Uab = 0;
        $cona->povrsinaOvoja = 0;
        $cona->transparentnaPovrsina = 0;
        foreach ($cona->ovoj->netransparentneKonstrukcije as $elementOvoja) {
            $skupni_Uab += $elementOvoja->U * $elementOvoja->povrsina * $elementOvoja->b * $elementOvoja->stevilo;
            $cona->povrsinaOvoja += $elementOvoja->povrsina * $elementOvoja->stevilo;
        }
        foreach ($cona->ovoj->transparentneKonstrukcije as $elementOvoja) {
            $skupni_Uab += $elementOvoja->U * $elementOvoja->povrsina * $elementOvoja->b * $elementOvoja->stevilo;
            $cona->povrsinaOvoja +=
                $elementOvoja->povrsina * $elementOvoja->stevilo;
            $cona->transparentnaPovrsina +=
                $elementOvoja->povrsina * (1 - $elementOvoja->delezOkvirja) * $elementOvoja->stevilo;
        }

        $cona->specTransmisijskeIzgube = $skupni_Uab + $cona->povrsinaOvoja * $cona->deltaPsi;
        $cona->specVentilacijskeIzgube = $cona->Hve_ogrevanje;

        $cona->specLetnaToplota = $cona->skupnaEnergijaOgrevanje / $cona->ogrevanaPovrsina;
        $cona->specLetniHlad = $cona->skupnaEnergijaHlajenje / $cona->ogrevanaPovrsina;

        $cona->faktorOblike = round($cona->povrsinaOvoja / $cona->brutoProstornina, 3);

        $cona->specKoeficientTransmisijskihIzgub = $skupni_Uab / $cona->povrsinaOvoja + $cona->deltaPsi;

        $povprecnaLetnaTemp = $okolje->povprecnaLetnaTemp < 7 ? 7 :
            ($okolje->povprecnaLetnaTemp > 11 ? 11 : $okolje->povprecnaLetnaTemp);
        $faktorOblike = $cona->faktorOblike < 0.2 ? 0.2 : ($cona->faktorOblike > 1.2 ? 1.2 : $cona->faktorOblike);

        $cona->dovoljenSpecKoeficientTransmisijskihIzgub = 0.25 +
            $povprecnaLetnaTemp / 300 +
            0.04 / $faktorOblike +
            ($cona->transparentnaPovrsina / $cona->povrsinaOvoja) / 8;

        return $cona;
    }

    /**
     * Izračun ventilacijskih izgub za cono
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public static function izracunVentilacijskihIzgub($cona, $okolje)
    {
        // poračun ventilacijskih izgub
        $faktorVetra = $cona->infiltracija->zavetrovanost == 1 ? 15 : 20;

        $faktorLokacijeLookup = [
            0 => [0.1, 0.07, 0.04],
            1 => [0.03, 0.02, 0.01],
        ];
        $faktorLokacije = $faktorLokacijeLookup[$cona->infiltracija->zavetrovanost - 1][$cona->infiltracija->lega - 1];

        switch ($cona->prezracevanje->vrsta) {
            case 'naravno':
                $cona->Hve_ogrevanje = 0.33 * $cona->netoProstornina * $cona->prezracevanje->izmenjava->ogrevanje;
                $cona->Hve_hlajenje = 0.33 * $cona->netoProstornina * $cona->prezracevanje->izmenjava->hlajenje;
                break;
            case 'mehansko':
                $Vinf_ogrevanje = $cona->netoProstornina * $cona->infiltracija->n50 * $faktorLokacije /
                    (1 + $faktorVetra / $faktorLokacije *
                    pow($cona->prezracevanje->volumenDovedenegaZraka->ogrevanje /
                        ($cona->netoProstornina * $cona->infiltracija->n50), 2));
                $Vinf_hlajenje = $cona->netoProstornina * $cona->infiltracija->n50 * $faktorLokacije /
                    (1 + $faktorVetra / $faktorLokacije *
                    pow($cona->prezracevanje->volumenDovedenegaZraka->hlajenje /
                    ($cona->netoProstornina * $cona->infiltracija->n50), 2));

                $cona->Hve_ogrevanje =
                    0.33 * ($cona->prezracevanje->volumenDovedenegaZraka->ogrevanje + $Vinf_ogrevanje);
                $cona->Hve_hlajenje =
                    0.33 * ($cona->prezracevanje->volumenDovedenegaZraka->hlajenje + $Vinf_hlajenje);
                break;
            case 'rekuperacija':
                $Vinf_ogrevanje = $cona->netoProstornina * $cona->infiltracija->n50 * $faktorLokacije;
                $Vinf_hlajenje = $cona->netoProstornina * $cona->infiltracija->n50 * $faktorLokacije;
                $cona->Hve_ogrevanje = 0.33 * ($Vinf_ogrevanje +
                    (1 - $cona->prezracevanje->izkoristek) * $cona->prezracevanje->volumenDovedenegaZraka->ogrevanje);
                $cona->Hve_hlajenje = 0.33 * ($Vinf_hlajenje +
                    $cona->prezracevanje->volumenDovedenegaZraka->hlajenje);
                break;
            default:
                $cona->Hve_ogrevanje = 0;
                $cona->Hve_hlajenje = 0;
                Log::error('Vrsta prezračevanja je neveljavna!');
        }

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            switch ($cona->prezracevanje->vrsta) {
                case 'naravno':
                    $cona->prezracevalneIzgubeOgrevanje[$mesec] = $cona->Hve_ogrevanje *
                        ($cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec]) * $stDni * 24 / 1000;
                    $cona->prezracevalneIzgubeHlajenje[$mesec] = $cona->Hve_hlajenje *
                        ($cona->notranjaTHlajenje - $okolje->zunanjaT[$mesec]) * $stDni * 24 / 1000;
                    break;
                case 'mehansko':
                    $cona->prezracevalneIzgubeOgrevanje[$mesec] = $cona->Hve_ogrevanje *
                        ($cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec]) * $stDni * 24 / 1000;
                    $cona->prezracevalneIzgubeHlajenje[$mesec] = $cona->Hve_hlajenje *
                        ($cona->notranjaTHlajenje - $okolje->zunanjaT[$mesec]) * $stDni * 24 / 1000;
                    break;
                case 'rekuperacija':
                    $cona->prezracevalneIzgubeOgrevanje[$mesec] = $cona->Hve_ogrevanje *
                        ($cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec]) * $stDni * 24 / 1000;
                    $cona->prezracevalneIzgubeHlajenje[$mesec] = $cona->Hve_hlajenje *
                        ($cona->notranjaTHlajenje - $okolje->zunanjaT[$mesec]) * $stDni * 24 / 1000;

                    break;
                default:
                    $cona->prezracevalneIzgubeOgrevanje[$mesec] = 0;
                    $cona->prezracevalneIzgubeHlajenje[$mesec] = 0;
                    Log::error('Vrsta prezračevanja je neveljavna!');
            }
        }
    }

    /**
     * Izračun faktorja izkoristka za ogrevanje
     *
     * @param \stdClass $cona Cona
     * @param \stdClass $okolje Okolje
     * @return void
     */
    public static function izracunFaktorjaIzkoristka($cona, $okolje)
    {
        $Cm_eff = $cona->ogrevanaPovrsina * $cona->toplotnaKapaciteta;

        $protiZraku_Uab = 0;
        $protiZraku_povrsina = 0;
        foreach ($cona->ovoj->transparentneKonstrukcije as $elementOvoja) {
            $protiZraku_Uab += $elementOvoja->U * $elementOvoja->povrsina * $elementOvoja->b * $elementOvoja->stevilo;
            $protiZraku_povrsina += $elementOvoja->povrsina * $elementOvoja->stevilo;
        }
        foreach ($cona->ovoj->netransparentneKonstrukcije as $elementOvoja) {
            if ($elementOvoja->protiZraku) {
                $protiZraku_Uab +=
                    $elementOvoja->U * $elementOvoja->povrsina * $elementOvoja->b * $elementOvoja->stevilo;
                $protiZraku_povrsina += $elementOvoja->povrsina * $elementOvoja->stevilo;
            }
        }

        $cona->Htr_ogrevanje = $protiZraku_Uab + $protiZraku_povrsina * $cona->deltaPsi;
        $cona->Htr_hlajenje = $cona->Htr_ogrevanje;

        $tau_ogrevanje = $Cm_eff / 3600 / ($cona->Htr_ogrevanje + $cona->Hgr_ogrevanje + $cona->Hve_ogrevanje);
        $A_ogrevanje = 1 + $tau_ogrevanje / 15;

        $tau_hlajenje = $Cm_eff / 3600 / ($cona->Htr_hlajenje + $cona->Hgr_hlajenje + $cona->Hve_hlajenje);
        $A_hlajenje = 1 + $tau_hlajenje / 15;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            $vsotaVirov_ogrevanje =
                $cona->notranjiViriOgrevanje[$mesec] +
                $cona->solarniDobitkiOgrevanje[$mesec] +
                ($cona->vracljiveIzgube[$mesec] ?? 0);

            $gama_ogrevanje = $vsotaVirov_ogrevanje /
                ($cona->prezracevalneIzgubeOgrevanje[$mesec] + $cona->transIzgubeOgrevanje[$mesec]);

            $cona->ucinekDobitkov[$mesec] = null;
            if ($gama_ogrevanje > -0.1 && $gama_ogrevanje < 2) {
                if ($gama_ogrevanje < 0) {
                    if ((float)$vsotaVirov_ogrevanje > 0) {
                        $cona->ucinekDobitkov[$mesec] = 1;
                    } else {
                        $cona->ucinekDobitkov[$mesec] = 1 / $gama_ogrevanje;
                    }
                } else {
                    if ($gama_ogrevanje == 1) {
                        $cona->ucinekDobitkov[$mesec] = $A_ogrevanje / ($A_ogrevanje + 1);
                    } else {
                        $cona->ucinekDobitkov[$mesec] =
                            (1 - pow($gama_ogrevanje, $A_ogrevanje)) / (1 - pow($gama_ogrevanje, $A_ogrevanje + 1));
                    }
                }
            }

            $vsotaVirov_hlajenje = $cona->notranjiViriHlajenje[$mesec] + $cona->solarniDobitkiHlajenje[$mesec];
            $gama_hlajenje = $vsotaVirov_hlajenje /
                ($cona->prezracevalneIzgubeHlajenje[$mesec] + $cona->transIzgubeHlajenje[$mesec]);

            $cona->Hgn[$mesec] = null;

            $cona->ucinekPonorov[$mesec] = null;
            if (1 / $gama_hlajenje <= 2) {
                if ($gama_hlajenje <= 0) {
                    $cona->ucinekPonorov[$mesec] = 1;
                } else {
                    if ($gama_hlajenje == 1) {
                        $cona->ucinekPonorov[$mesec] = $A_hlajenje / ($A_hlajenje + 1);
                    } else {
                        $cona->ucinekPonorov[$mesec] =
                            (1 - pow($gama_hlajenje, -$A_hlajenje)) / (1 - pow($gama_hlajenje, -$A_hlajenje - 1));
                    }
                }
            }
        }
    }

    /**
     * Izračun potrebne energije za ogrevanje in hlajenje
     *
     * @param \stdClass $cona Cona
     * @param \stdClass $okolje Okolje
     * @return void
     */
    public static function izracunEnergijeOgrevanjeHlajanje($cona, $okolje)
    {
        // izračun skupne potrebne toplote
        $cona->skupnaEnergijaOgrevanje = 0;
        $cona->skupnaEnergijaHlajenje = 0;
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $cona->energijaOgrevanje[$mesec] = 0;
            if ($cona->ucinekDobitkov[$mesec]) {
                $cona->energijaOgrevanje[$mesec] =
                    $cona->transIzgubeOgrevanje[$mesec] + $cona->prezracevalneIzgubeOgrevanje[$mesec] -
                    $cona->ucinekDobitkov[$mesec] * ($cona->notranjiViriOgrevanje[$mesec] +
                    $cona->solarniDobitkiOgrevanje[$mesec] + ($cona->vracljiveIzgube[$mesec] ?? 0));

                if ($cona->energijaOgrevanje[$mesec] < 0) {
                    $cona->energijaOgrevanje[$mesec] = 0;
                }
            }
            $cona->skupnaEnergijaOgrevanje += $cona->energijaOgrevanje[$mesec];

            $cona->energijaHlajenje[$mesec] = 0;
            if ($cona->ucinekPonorov[$mesec]) {
                $cona->energijaHlajenje[$mesec] =
                    $cona->notranjiViriHlajenje[$mesec] + $cona->solarniDobitkiHlajenje[$mesec] -
                    $cona->ucinekPonorov[$mesec] * ($cona->transIzgubeHlajenje[$mesec] +
                    $cona->prezracevalneIzgubeHlajenje[$mesec]);
            }
            $cona->skupnaEnergijaHlajenje += $cona->energijaHlajenje[$mesec];
        }
    }

    /**
     * Izračun energije za TSV
     *
     * @param \stdClass $cona Cona
     * @param \stdClass $okolje Okolje
     * @return void
     */
    public static function izracunTSV($cona, $okolje)
    {
        if (empty($cona->TSV->steviloOseb)) {
            switch ($cona->klasifikacija) {
                case 'St-1':
                    $cona->TSV->steviloOseb = 0.025 * $cona->ogrevanaPovrsina;
                    if ($cona->TSV->steviloOseb > 1.75) {
                        $cona->TSV->steviloOseb = 1.75 + 0.3 * ($cona->TSV->steviloOseb - 1.75);
                    }
                    break;
                case 'St-2':
                case 'St-3':
                    if ($cona->ogrevanaPovrsina > 50) {
                        $cona->TSV->steviloOseb = 0.035 * $cona->ogrevanaPovrsina;
                        if ($cona->TSV->steviloOseb > 1.75) {
                            $cona->TSV->steviloOseb = 1.75 + 0.3 * (0.035 * $cona->ogrevanaPovrsina - 1.75);
                        }
                    } else {
                        $cona->TSV->steviloOseb = 1.75 - 0.01875 * (50 - $cona->ogrevanaPovrsina);
                        if ($cona->TSV->steviloOseb > 1.75) {
                            $cona->TSV->steviloOseb = 1.75 + 0.3 * (0.035 * $cona->ogrevanaPovrsina - 1.75);
                        }
                    }
                    break;
                default:
                    Log::error('TSV: Klasifikacija cone je neveljavna');
            }
        }
        if (empty($cona->TSV->dnevnaKolicina)) {
            $cona->TSV->dnevnaKolicina = min(40.71, 3.26 * $cona->ogrevanaPovrsina / $cona->TSV->steviloOseb);
        }

        $cona->TSV->toplaVodaT = $cona->TSV->toplaVodaT ?? 42;
        $cona->TSV->hladnaVodaT = $cona->TSV->hladnaVodaT ?? 10;

        $cona->skupnaPotrebaTSV = 0;
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            $cona->potrebaTSV[$mesec] = 0.001 * $cona->TSV->dnevnaKolicina * $cona->TSV->steviloOseb * 4.2 / 3.6 *
                ($cona->TSV->toplaVodaT - $cona->TSV->hladnaVodaT) * $stDni;

            $cona->skupnaPotrebaTSV += $cona->potrebaTSV[$mesec];
        }
    }

    /**
     * Izračun električne energije za razsvetljavo
     *
     * @param \stdClass $cona Cona
     * @param \stdClass $okolje Okolje
     * @return void
     */
    public static function izracunRazsvetljave($cona, $okolje)
    {
        $cona->razsvetljava = $cona->razsvetljava ?? new \stdClass();

        // fluo     1-brez zatemnjevanja        0.9-z zatemnjevanjem
        // LED      1-brez zatemnjevanja        0.85-z zatemnjevanjem
        $cona->razsvetljava->faktorZmanjsanjaSvetlobnegaToka =
            $cona->razsvetljava->faktorZmanjsanjaSvetlobnegaToka ?? 1;

        // stanovanjske     0.7-ročni vklop     0.55-avtomatsko zatemnjevanje       0.5-ročni vklop, samodejni izklop
        // pisarne          0.9-ročni vklop     0.85-avtomatsko zatemnjevanje       0.7-ročni vklop, samodejni izklop
        // ostale stavbe    1-za vse načine krmiljenja
        $cona->razsvetljava->faktorPrisotnosti = $cona->razsvetljava->faktorPrisotnosti ?? 0.7;

                // halogen      30 lm/W
                // fluo         80 lm/W
                // LED          100-140 lm/W
                // ref.stavba   65 lm/W oz. 95 lm/W po letu 2025
                $ucinkovitostViraSvetlobe = $cona->razsvetljava->ucinkovitostViraSvetlobe ?? 65;

                // stanovanjske 300 lx
                // poslovne     500 lx
                $osvetlitevDelovnePovrsine = $cona->razsvetljava->osvetlitevDelovnePovrsine ?? 300;

                // k = TSG stran 95
                $faktorOblikeCone = $cona->razsvetljava->faktorOblikeCone ?? 1;

                // F_CA = TSG stran 96
                $faktorZmanjsaneOsvetlitveDelovnePovrsine =
                    $cona->razsvetljava->faktorZmanjsaneOsvetlitveDelovnePovrsine ?? 1;

                // CFL fluo     1.15
                // T5 fluo      1.1
                // LED          1
                $faktorVzdrzevanja = $cona->razsvetljava->faktorVzdrzevanja ?? 1;

        $cona->razsvetljava->mocSvetilk = $cona->razsvetljava->mocSvetilk ??
            $ucinkovitostViraSvetlobe * $osvetlitevDelovnePovrsine * $faktorOblikeCone *
            $faktorZmanjsaneOsvetlitveDelovnePovrsine * $faktorVzdrzevanja;

        $cona->razsvetljava->faktorNaravneOsvetlitve = $cona->razsvetljava->faktorNaravneOsvetlitve ?? 0.6;

        $cona->razsvetljava->letnoUrPodnevi = $cona->razsvetljava->letnoUrPodnevi ?? 1820;
        $cona->razsvetljava->letnoUrPonoci = $cona->razsvetljava->letnoUrPonoci ?? 1680;

        $cona->razsvetljava->varnostna = $cona->razsvetljava->varnostna ?? new \stdClass();
        $cona->razsvetljava->varnostna->energijaZaPolnjenje = $cona->razsvetljava->varnostna->energijaZaPolnjenje ?? 0;
        $cona->razsvetljava->varnostna->energijaZaDelovanje = $cona->razsvetljava->varnostna->energijaZaDelovanje ?? 0;

        $letnaDovedenaEnergija = ($cona->razsvetljava->faktorZmanjsanjaSvetlobnegaToka *
            $cona->razsvetljava->faktorPrisotnosti *
            $cona->razsvetljava->mocSvetilk / 1000 *
            (($cona->razsvetljava->letnoUrPodnevi * $cona->razsvetljava->faktorNaravneOsvetlitve) +
            $cona->razsvetljava->letnoUrPonoci) +
            $cona->razsvetljava->varnostna->energijaZaPolnjenje +
            $cona->razsvetljava->varnostna->energijaZaDelovanje) * $cona->ogrevanaPovrsina;

        $mesecniUtezniFaktor = [1.25, 1.1, 0.94, 0.86, 0.83, 0.73, 0.79, 0.87, 0.94, 1.09, 1.21, 1.35];

        $cona->skupnaPotrebaRazsvetljava = 0;
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            $cona->potrebaRazsveljava[$mesec] = $letnaDovedenaEnergija * $stDni / 365 * $mesecniUtezniFaktor[$mesec];

            $cona->skupnaPotrebaRazsvetljava += $cona->potrebaRazsveljava[$mesec];
        }
    }

    /**
     * Izračun navlaževanje
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolj
     * @param array $options Opcije za izračun
     * @return void
     */
    public static function izracunNavlazevanje($cona, $okolje, $options = [])
    {
        if (empty($cona->uravnavanjeVlage)) {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $cona->energijaNavlazevanje[$mesec] = 0;
                $cona->energijaRazvlazevanje[$mesec] = 0;
            }
            $cona->skupnaEnergijaNavlazevanje = 0;
            $cona->skupnaEnergijaRazvlazevanje = 0;
        } else {
            $uparjalnaToplota = 2466;

            $cona->uravnavanjeVlage->vlaznostZrakaNavlazevanje =
                $cona->uravnavanjeVlage->vlaznostZrakaNavlazevanje ?? 30;
            $cona->uravnavanjeVlage->vlaznostZrakaRazvlazevanje =
                $cona->uravnavanjeVlage->vlaznostZrakaRazvlazevanje ?? 60;

            // tabela 7.1.1
            $cona->uravnavanjeVlage->faktorUporabe = $cona->uravnavanjeVlage->faktorUporabe ?? 1;

            // specifična količina oddane vodne pare virov v stavbi na m2
            // g_h2o,h (kg/m2h)
            $cona->uravnavanjeVlage->viriVodnePare = $cona->uravnavanjeVlage->viriVodnePare ?? 1.4;

            // samo za navlaževanje
            $cona->uravnavanjeVlage->ucinkovitostPrenosnika = $cona->uravnavanjeVlage->ucinkovitostPrenosnika ?? 0.55;

            $nasicenNotranjiTlakOgrevanje =
                611.2 * exp(17.62 * $cona->notranjaTOgrevanje / (243.12 + $cona->notranjaTOgrevanje));
            $nasicenNotranjiTlakHlajenje =
                611.2 * exp(17.62 * $cona->notranjaTHlajenje / (243.12 + $cona->notranjaTHlajenje));

            // x_i,a,min,m
            $minNotranjaVlaznostOgrevanje = $cona->uravnavanjeVlage->minNotranjaVlaznostOgrevanje ??
                0.622 * 1000 * $cona->uravnavanjeVlage->vlaznostZrakaNavlazevanje / 100 *
                $nasicenNotranjiTlakOgrevanje /
                (101325 - $cona->uravnavanjeVlage->vlaznostZrakaNavlazevanje / 100 * $nasicenNotranjiTlakOgrevanje);
            $minNotranjaVlaznostHlajenje = $cona->uravnavanjeVlage->minNotranjaVlaznostHlajenje ??
                0.622 * 1000 * $cona->uravnavanjeVlage->vlaznostZrakaNavlazevanje / 100 *
                $nasicenNotranjiTlakHlajenje /
                (101325 - $cona->uravnavanjeVlage->vlaznostZrakaNavlazevanje / 100 * $nasicenNotranjiTlakHlajenje);

            $cona->skupnaEnergijaNavlazevanje = 0;
            $cona->skupnaEnergijaRazvlazevanje = 0;
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

                $nasicenZunanjiTlak =
                    611.2 * exp(17.62 * $okolje->zunanjaT[$mesec] / (243.12 + $okolje->zunanjaT[$mesec]));

                // X_e,a,m
                $absZunanjaVlaznost = $okolje->absVlaznost[$mesec] ??
                    (0.622 * 1000 * $okolje->zunanjaVlaga[$mesec] / 100 *
                    $nasicenZunanjiTlak /
                    (101325 - $okolje->zunanjaVlaga[$mesec] / 100 * $nasicenZunanjiTlak));

                // m_h2o,HU,m
                $potrebnaMesecnaKolicinaVodeOgrevanje =
                    0.001 * 1.25 * $cona->prezracevanje->volumenDovedenegaZraka->ogrevanje *
                    ($minNotranjaVlaznostOgrevanje - $absZunanjaVlaznost) * 24 * $stDni;
                $potrebnaMesecnaKolicinaVodeHlajenje =
                    0.001 * 1.25 * $cona->prezracevanje->volumenDovedenegaZraka->hlajenje *
                    ($absZunanjaVlaznost - $minNotranjaVlaznostHlajenje) * 24 * $stDni;

                // G_h2o,m
                $mesecnaKolicinaVodnePare = $cona->uravnavanjeVlage->faktorUporabe *
                    $cona->uravnavanjeVlage->viriVodnePare * $cona->ogrevanaPovrsina * 24 * $stDni / 1000;

                $cona->energijaNavlazevanje[$mesec] =
                    ($potrebnaMesecnaKolicinaVodeOgrevanje - $mesecnaKolicinaVodnePare) *
                    (1 - $cona->uravnavanjeVlage->ucinkovitostPrenosnika) * $uparjalnaToplota / 3600;
                $cona->energijaRazvlazevanje[$mesec] =
                    ($potrebnaMesecnaKolicinaVodeHlajenje + $mesecnaKolicinaVodnePare) * $uparjalnaToplota / 3600;

                if (!empty($options['details'])) {
                    // za validacijo
                    $cona->uravnavanjeVlage->absZunanjaVlaznost[$mesec] = $absZunanjaVlaznost;
                    $cona->uravnavanjeVlage->potrebnaMesecnaKolicinaVodeOgrevanje[$mesec] =
                        $potrebnaMesecnaKolicinaVodeOgrevanje;
                    $cona->uravnavanjeVlage->potrebnaMesecnaKolicinaVodeHlajenje[$mesec] =
                        $potrebnaMesecnaKolicinaVodeHlajenje;
                    $cona->uravnavanjeVlage->mesecnaKolicinaVodnePare[$mesec] = $mesecnaKolicinaVodnePare;
                }

                $cona->skupnaEnergijaNavlazevanje += $cona->energijaNavlazevanje[$mesec];
                $cona->skupnaEnergijaRazvlazevanje += $cona->energijaRazvlazevanje[$mesec];
            }
        }
    }
}
