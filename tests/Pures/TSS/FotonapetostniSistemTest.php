<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS;

use App\Calc\GF\TSS\FotonapetostniSistemi\FotonapetostniSistem;
use App\Lib\Calc;
use PHPUnit\Framework\TestCase;

/**
 * Validacija mesečne metode za fotonapetostni sistem brez baterije.
 * TSG-1-004:2022, tabela 9.3: faktor ujemanja fmatch,m in v stavbi porabljena energija
 * Eel,pr-use,m = MIN(Eel,pr,on-site,m; Eel,use,m) * fmatch,m.
 */
final class FotonapetostniSistemTest extends TestCase
{
    public function testValidacijaTSG93FaktorUjemanja(): void
    {
        // TSG tabela 9.3
        $proizvodnja = [136, 213, 323, 400, 469, 470, 517, 478, 357, 229, 120, 98]; // Eel,pr,on-site,m
        $poraba = [148, 123, 106, 89, 77, 72, 79, 78, 78, 105, 130, 149]; // Eel,use,m
        $expectedFmatch = [0.5, 0.57, 0.7, 0.79, 0.84, 0.85, 0.85, 0.84, 0.79, 0.62, 0.5, 0.54];
        $expectedPrUse = [68, 70, 75, 70, 65, 61, 67, 66, 62, 65, 60, 53]; // Eel,pr-use,m

        // PV sistem brez baterije, brez oddaje v omrežje -> upošteva se faktor ujemanja (vplivUjemanja = true)
        $cfg = (object)[
            'id' => 'PV',
            'orientacija' => 'J',
            'naklon' => 30,
            'povrsina' => 1,
            'vrsta' => 'polikristalne',
            'vgradnja' => 'dobroPrezracevani',
            'oddajaVOmrezje' => false,
        ];
        $pv = new FotonapetostniSistem(clone $cfg);

        // sestavi okolje tako, da mesečna proizvodnja ustreza tabeli 9.3
        $kpk = $pv->vrsta->koeficientMoci();
        $fperf = $pv->vgradnja->koeficientVgradnje();
        $obsevanje = [];
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            // proizvodnja = povrsina * (stDni * obsevanje / 1000) * kpk * fperf  =>  obsevanje
            $obsevanje[$mesec] = $proizvodnja[$mesec] * 1000 / ($stDni * $kpk * $fperf);
        }
        $okolje = new \stdClass();
        $okolje->obsevanje = [(object)['orientacija' => 'J', 'naklon' => 30, 'obsevanje' => $obsevanje]];

        $pv->analiza($poraba, $okolje);

        $this->assertTrue($pv->vplivUjemanja, 'PV brez baterije in brez oddaje: upošteva se faktor ujemanja');

        // proizvodnja se mora ujemati z vhodom (kontrola sestave okolja)
        $this->assertEquals(
            $proizvodnja,
            array_map(fn($el) => (int)round($el), $pv->proizvedenaElektricnaEnergija)
        );

        // faktor ujemanja fmatch,m
        $this->assertEquals(
            $expectedFmatch,
            array_map(fn($el) => round($el, 2), $pv->faktorUjemanja)
        );

        // v stavbi porabljena (samooskrbna) energija Eel,pr-use,m
        $this->assertEquals(
            $expectedPrUse,
            array_map(fn($el) => (int)round($el), $pv->porabljenaEnergija)
        );
    }
}
