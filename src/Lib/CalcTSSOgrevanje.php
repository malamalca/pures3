<?php
declare(strict_types=1);

namespace App\Lib;

use App\Core\Log;

class CalcTSSOgrevanje
{
    /**
     * Glavna metoda za analizo ogrevanja
     *
     * @param \StdClass $sistem Podatki sistema
     * @param \StdClass $cona Podatki cone
     * @param \StdClass $okolje Podatki okolja
     * @param \StdClass $splosniPodatki Podatki stavbe
     * @return \StdClass
     */
    public static function analiza($sistem, $cona, $okolje, $splosniPodatki)
    {
        $sistem->stevilo = $sistem->stevilo ?? 1;

        $vrsteSistemov = ['elektricni', 'sevala', 'toplozracni', 'toplovodni', 'biomasa'];

        $sistem->vrsta = $sistem->vrsta ?? 'elektricni';
        if (!in_array($sistem->vrsta, $vrsteSistemov)) {
            $sistem->vrsta = 'elektricni';
        }

        $vrsteEnergentov = ['elektrika', 'zemeljskiPlin', 'UNP', 'ELKO', 'drva', 'peleti/sekanci', 'daljinsko'];
        $sistem->energent = $sistem->energent ?? 'elektrika';

        if (!empty($sistem->prenosniki)) {
            foreach ($sistem->prenosniki as $prenosnik) {
                self::analizaKoncnihPrenosnikov($prenosnik, $sistem, $cona, $okolje);
            }
        }

        if (!empty($sistem->razvodi)) {
            foreach ($sistem->razvodi as $razvod) {
                self::analizaRazvoda($razvod, $sistem, $cona, $okolje);
            }
        }
    }

    public static function analizaKoncnihPrenosnikov($prenosnik, $sistem, $cona, $okolje)
    {
        $Cm_eff = $cona->ogrevanaPovrsina * $cona->toplotnaKapaciteta;
        $tau_ogrevanje = $Cm_eff / 3600 / ($cona->specTransmisijskeIzgube + $cona->specVentilacijskeIzgube);
        $a_ogrevanje = 1 + ($tau_ogrevanje / 15);

        $stOgreval = $prenosnik->stOgreval ?? 1;

        $vrstePrenosnikov = ['radiatorji', 'konvektorji', 'ploskovnaOgrevala'];
        if (empty($prenosnik->vrsta) || !in_array($prenosnik->vrsta, $vrstePrenosnikov)) {
            throw new \Exception(sprintf('TSS Ogrevanje | Prenosniki : Vrsta "%s" ne obstaja', $prenosnik->vrsta));
        }

        // Δθhydr - deltaTemp za hidravlično uravnoteženje sistema; prvi stolpec za stOgreval <= 10, drugi za > 10
        $prenosnik->hidravlicnoUravnotezenje = $prenosnik->hidravlicnoUravnotezenje ?? 'neuravnotezeno';
        $hidrFaktorji = [
            'neuravnotezeno' => [0.6, 0.6],
            'staticnoKoncnihPrenosnikov' => [0.3, 0.4],
            'staticnoDviznihVodov' => [0.2, 0.3],
            'dinamicnoPolnaObremenitev' => [0.1, 0.2],
            'dinamicnoDelnaObremenitev' => [0, 0],
        ];
        $indexHidrFaktorji = $stOgreval > 10 ? 1 : 0;
        $deltaT_hidravlicnoUravnotezenje = $hidrFaktorji[$prenosnik->hidravlicnoUravnotezenje][$indexHidrFaktorji];

        Log::info('deltaT_hyd: ' . $deltaT_hidravlicnoUravnotezenje);

        // Δθctr - deltaTemp za regulacijo temperature; prvi stolpec sevala, drugi stolpec toplovod, h<4m
        $prenosnik->regulacijaTemperature = $prenosnik->regulacijaTemperature ?? 'centralna';
        $regFaktorji = [
            'centralna' => [2.5, 2.5],
            'referencniProstor' => [1.6, 1.6],
            'P-krmilnik' => [0.7, 0.7],
            'PI-krmilnik' => [0.7, 0.7],
            'PI-krmilnikZOptimizacijo' => [0.5, 0.5],
        ];
        $indexRegFaktorji = 0;
        $deltaT_regulacijaTemperature = $regFaktorji[$prenosnik->regulacijaTemperature][$indexRegFaktorji];
        Log::info('deltaT_ctr: ' . $deltaT_regulacijaTemperature);

        // stolpci emb1/str1, niz, nin1, ft_tč
        $sistemFaktorji = [
            'talno_mokri' => [0],
            'talno_suhi' => [0],
            'talno_suhiTankaObloga' => [0],
            'stensko' => [0.4],
            'stopno' => [0.7],
        ];

        // stolpci emb h>4, emb2, nin2
        $izolacijaFaktorji = [
            'brez' => [1.9, 1.4, 0.86],
            'min' => [1, 0.5, 0.95],
            '100%' => [0, 0.1, 0.99],
        ];

        // Δθemb - deltaTemp za izolacijo (polje R206)
        $deltaT_emb = 0;
        if ($prenosnik->vrsta == 'ploskovnaOgrevala') {
            if (empty($prenosnik->sistem) || !in_array($prenosnik->sistem, array_keys($sistemFaktorji))) {
                throw new \Exception(sprintf('TSS Ogrevanje | Prenosniki : Sistem "%s" ne obstaja', $prenosnik->sistem));
            }
            if (empty($prenosnik->sistem) || !in_array($prenosnik->izolacija, array_keys($izolacijaFaktorji))) {
                throw new \Exception(sprintf('TSS Ogrevanje | Prenosniki : Izolacija "%s" ne obstaja', $prenosnik->izolacija));
            }
            $deltaT_emb = $sistemFaktorji[$prenosnik->sistem][0] + $izolacijaFaktorji[$prenosnik->izolacija][1];
        }
        Log::info('deltaT_emb: ' . $deltaT_emb);

        $namestitevFaktorji = [
            'notranjeStene' => 1.3,
            'zunanjeStene' => 0.3,
            'zasteklitevBrezZascite' => 1.7,
            'zasteklitevZZascito' => 1.2,
        ];

        // stolpci str1
        $rezimFaktorji = [
            '35/30' => [0.4],
            '40/30' => [0.5],
            '55/45' => [0.7],
        ];

        // Δθstr - deltaTemp Str (polje Q208)
        $deltaT_str = 0;
        switch ($prenosnik->vrsta) {
            case 'ploskovnaOgrevala':
                if (empty($prenosnik->sistem) || !in_array($prenosnik->sistem, array_keys($sistemFaktorji))) {
                    throw new \Exception(sprintf('TSS Ogrevanje | Prenosniki : Sistem "%s" ne obstaja', $prenosnik->sistem));
                }
                $deltaT_str = $sistemFaktorji[$prenosnik->sistem][0];
                break;
            case 'radiatorji':
                if (empty($prenosnik->namestitev) || !in_array($prenosnik->namestitev, array_keys($namestitevFaktorji))) {
                    throw new \Exception(sprintf('TSS Ogrevanje | Prenosniki : Namestitev "%s" ne obstaja', $prenosnik->namestitev));
                }
                if (empty($prenosnik->rezim) || !in_array($prenosnik->rezim, array_keys($rezimFaktorji))) {
                    throw new \Exception(sprintf('TSS Ogrevanje | Prenosniki : Režim "%s" ne obstaja', $prenosnik->rezim));
                }
                $deltaT_str = $namestitevFaktorji[$prenosnik->namestitev] + $rezimFaktorji[$prenosnik->rezim][0];
                break;
        }
        Log::info('deltaT_str: ' . $deltaT_str);

        // celica G244
        switch ($sistem->vrsta) {
            case 'elektricni':
                // TODO:
                break;
            case 'sevala':
                // TODO:
                break;
            case 'toplozracni':
                // TODO:
                break;
            case 'toplovodni':
                $deltaT_notranja = $deltaT_str + $deltaT_emb + $deltaT_hidravlicnoUravnotezenje + $deltaT_regulacijaTemperature;
                break;
            case 'biomasa':
                // TODO:
                break;
        }

        Log::info('deltaT_notranja: ' . $deltaT_notranja);

        $prenosnik->skupneIzgube = 0;
        $prenosnik->skupneIzgubeEmisij = 0;
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $vracljiveIzgube = 0;

            $gama = ($cona->solarniDobitkiOgrevanje[$mesec] + $cona->notranjiViriOgrevanje[$mesec] + $vracljiveIzgube) /
                ($cona->transIzgubeOgrevanje[$mesec] + $cona->prezracevalneIzgubeOgrevanje[$mesec]);

            // TODO: Preveri... v excelu V150 je čuden pogoj za izračun $gama
            $ucinekDobitkov = null;
            if ($gama > 0 && $gama < 2) {
                if ($gama == 1) {
                    $ucinekDobitkov = $a_ogrevanje / ($a_ogrevanje + 1);
                } else {
                    $ucinekDobitkov = (1 - pow($gama, $a_ogrevanje)) / (1 - pow($gama, $a_ogrevanje + 1));
                }
            }

            // QH,nd,m; QH,nd,an
            $prenosnik->izgube[$mesec] = empty($ucinekDobitkov) ? 0 :
                $cona->transIzgubeOgrevanje[$mesec] + $cona->prezracevalneIzgubeOgrevanje[$mesec] -
                $ucinekDobitkov *
                ($cona->solarniDobitkiOgrevanje[$mesec] + $cona->notranjiViriOgrevanje[$mesec] + $vracljiveIzgube);

            $prenosnik->skupneIzgube += $prenosnik->izgube[$mesec];

            // TODO: izgube zaradi navlaževanja in razvkaževanja ?? v V150 je prazno

            // izgube emisij prenosnikov
            // QH,em,ls,m
            $deltaT = $deltaT_notranja / ($cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec]);
            $prenosnik->izgubeEmisij[$mesec] = $prenosnik->izgube[$mesec] * $deltaT;

            $prenosnik->skupneIzgubeEmisij += $prenosnik->izgubeEmisij[$mesec];
        }
    }

    public static function analizaRazvoda($razvod, $sistem, $cona, $okolje)
    {
    }
}
