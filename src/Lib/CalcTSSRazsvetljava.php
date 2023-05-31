<?php
declare(strict_types=1);

namespace App\Lib;

use App\Calc\TSS\TSSVrstaEnergenta;

class CalcTSSRazsvetljava
{
    /**
     * Glavna metoda za analizo razsvetljave
     *
     * @param \stdClass $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param \stdClass $splosniPodatki Podatki stavbe
     * @return \stdClass
     */
    public static function analiza($sistem, $cona, $okolje, $splosniPodatki)
    {
        // fluo     1-brez zatemnjevanja        0.9-z zatemnjevanjem
        // LED      1-brez zatemnjevanja        0.85-z zatemnjevanjem
        $sistem->faktorZmanjsanjaSvetlobnegaToka =
            $sistem->faktorZmanjsanjaSvetlobnegaToka ?? 1;

        // stanovanjske     0.7-ročni vklop     0.55-avtomatsko zatemnjevanje       0.5-ročni vklop, samodejni izklop
        // pisarne          0.9-ročni vklop     0.85-avtomatsko zatemnjevanje       0.7-ročni vklop, samodejni izklop
        // ostale stavbe    1-za vse načine krmiljenja
        $sistem->faktorPrisotnosti = $sistem->faktorPrisotnosti ?? 0.7;

                // halogen      30 lm/W
                // fluo         80 lm/W
                // LED          100-140 lm/W
                // ref.stavba   65 lm/W oz. 95 lm/W po letu 2025
                $ucinkovitostViraSvetlobe = $sistem->ucinkovitostViraSvetlobe ?? 65;

                // stanovanjske 300 lx
                // poslovne     500 lx
                $osvetlitevDelovnePovrsine = $sistem->osvetlitevDelovnePovrsine ?? 300;

                // k = TSG stran 95
                $faktorOblikeCone = $cona->faktorOblikeCone ?? 1;

                // F_CA = TSG stran 96
                $faktorZmanjsaneOsvetlitveDelovnePovrsine =
                    $sistem->faktorZmanjsaneOsvetlitveDelovnePovrsine ?? 1;

                // CFL fluo     1.15
                // T5 fluo      1.1
                // LED          1
                $faktorVzdrzevanja = $sistem->faktorVzdrzevanja ?? 1;

        $sistem->mocSvetilk = $sistem->mocSvetilk ??
            $ucinkovitostViraSvetlobe * $osvetlitevDelovnePovrsine * $faktorOblikeCone *
            $faktorZmanjsaneOsvetlitveDelovnePovrsine * $faktorVzdrzevanja;

        $sistem->faktorNaravneOsvetlitve = $sistem->faktorNaravneOsvetlitve ?? 0.6;

        $sistem->letnoUrPodnevi = $sistem->letnoUrPodnevi ?? 1820;
        $sistem->letnoUrPonoci = $sistem->letnoUrPonoci ?? 1680;

        $sistem->varnostna = $sistem->varnostna ?? new \stdClass();
        $sistem->varnostna->energijaZaPolnjenje = $sistem->varnostna->energijaZaPolnjenje ?? 0;
        $sistem->varnostna->energijaZaDelovanje = $sistem->varnostna->energijaZaDelovanje ?? 0;

        $letnaDovedenaEnergija = ($sistem->faktorZmanjsanjaSvetlobnegaToka *
            $sistem->faktorPrisotnosti *
            $sistem->mocSvetilk / 1000 *
            (($sistem->letnoUrPodnevi * $sistem->faktorNaravneOsvetlitve) +
            $sistem->letnoUrPonoci) +
            $sistem->varnostna->energijaZaPolnjenje +
            $sistem->varnostna->energijaZaDelovanje) * $cona->ogrevanaPovrsina;

        $mesecniUtezniFaktor = [1.25, 1.1, 0.94, 0.86, 0.83, 0.73, 0.79, 0.87, 0.94, 1.09, 1.21, 1.35];

        $sistem->skupnaDovodenaEnergija = 0;
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            $sistem->dovedenaEnergija[$mesec] = $letnaDovedenaEnergija * $stDni / 365 * $mesecniUtezniFaktor[$mesec];

            $sistem->skupnaDovodenaEnergija += $sistem->dovedenaEnergija[$mesec];

            $sistem->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] =
                ($sistem->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] ?? 0) +
                $sistem->dovedenaEnergija[$mesec];
        }

        return $sistem;
    }
}
