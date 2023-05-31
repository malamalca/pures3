<?php
declare(strict_types=1);

namespace App\Lib;

use App\Calc\TSS\TSSVrstaEnergenta;

class CalcTSSPrezracevanje
{
    /**
     * Glavna metoda za analizo prezracevalne naoprave
     *
     * @param \stdClass $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param \stdClass $splosniPodatki Podatki stavbe
     * @return \stdClass
     */
    public static function analiza($sistem, $cona, $okolje, $splosniPodatki)
    {
        $sistem->stevilo = $sistem->stevilo ?? 1;

        // privzamem ročni vklop
        $sistem->faktorKrmiljenja = $sistem->faktorKrmiljenja ?? 1;

        // sistem krmiljenja je mogoče podati tudi preko opisa
        if (isset($sistem->krmiljenje) && is_string($sistem->krmiljenje)) {
            $faktorjiSistemaKrmiljenja = [
                'rocniVklop' => 1, 'casovnik' => 0.95, 'centralnaRegulacija' => 0.85, 'lokalnaRegulacija' => 0.65,
            ];
            if (in_array($sistem->faktorKrmiljenja, array_keys($faktorjiSistemaKrmiljenja))) {
                $sistem->faktorKrmiljenja = $faktorjiSistemaKrmiljenja[$sistem->krmiljenje];
            } else {
                throw new \Exception('TSS Prezračevanje: Vpisani sistem krmiljenja ne obstaja.');
            }
        }

        $sistem->mocSenzorjev = $sistem->mocSenzorjev ?? 0;

        if (!isset($sistem->dovod)) {
            $sistem->dovod = new \stdClass();
        }
        if (!isset($sistem->odvod)) {
            $sistem->odvod = new \stdClass();
        }

        // filter je lahko 'brez', 'hepa', 'F'
        $sistem->dovod->filter = $sistem->dovod->filter ?? 'brez';
        $sistem->odvod->filter = $sistem->odvod->filter ?? 'brez';

        $sistem->dovod->volumen = $sistem->dovod->volumen ?? $cona->prezracevanje->volumenProjekt;
        $sistem->odvod->volumen = $sistem->odvod->volumen ?? $cona->prezracevanje->volumenProjekt;

        $sistem->dovod->mocVentilatorja =
            $sistem->dovod->mocVentilatorja ?? self::izracunMociVentilatorja('dovod', $sistem);

        $sistem->odvod->mocVentilatorja =
            $sistem->odvod->mocVentilatorja ?? self::izracunMociVentilatorja('odvod', $sistem);

        $sistem->skupnaDovodenaEnergija = 0;
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = $stDni * 24;

            $sistem->dovedenaEnergija[$mesec] = $stUr *
                (($sistem->dovod->mocVentilatorja + $sistem->odvod->mocVentilatorja) * $sistem->faktorKrmiljenja +
                $sistem->mocSenzorjev / 1000) * $sistem->stevilo;

            $sistem->skupnaDovodenaEnergija += $sistem->dovedenaEnergija[$mesec];

            $sistem->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] =
                ($sistem->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] ?? 0) +
                $sistem->dovedenaEnergija[$mesec];
        }

        return $sistem;
    }

    /**
     * Izračun moči ventilatorja, kadar moč ni vpisana neposredno (ni znana)
     *
     * @param string $smer Odvod/dovod
     * @param \stdClass $sistem Podatki sistema
     * @return float
     */
    public static function izracunMociVentilatorja($smer, $sistem)
    {
        $dodatkiFiltra = ['brez' => 0, 'hepa' => 1000, 'f' => 300];
        $dodatekFiltra = $dodatkiFiltra[strtolower($sistem->$smer->filter)] ?? 0;
        $dodatekH1H2 = !empty($sistem->razredH1H2) ? 300 : 0;

        // kW/(m3/h)
        $spf = 0;
        if ($smer == 'dovod') {
            $spf = (0.211 * 3600 + $dodatekFiltra + $dodatekH1H2) / 3600000;
        } elseif ($smer == 'odvod') {
            $spf = (0.142 * 3600 + $dodatekFiltra + $dodatekH1H2) / 3600000;
        }

        $mocVentilatorja = $spf * $sistem->$smer->volumen;

        return $mocVentilatorja;
    }
}
