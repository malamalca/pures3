<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\KoncniPrenosniki;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Konvektor;
use PHPUnit\Framework\TestCase;

final class KonvektorTest extends TestCase
{
    public function testToplotneIzgube(): void
    {
        $okolje = new \stdClass();
        $okolje->zunanjaT = [ -1, 1, 5, 9, 14, 17, 19, 19, 15, 10, 4, 0];

        $cona = new \stdClass();
        $cona->notranjaTOgrevanje = 20;

        $config = <<<EOT
        {
            "id": "KONVEKTOR",
            "vrsta": "konvektorji",

            "namestitev": "zasteklitevBrezZascite",

            "hidravlicnoUravnotezenje": "dinamicnoPolnaObremenitev",
            "regulacijaTemperature": "P-krmilnik",
            "stevilo": 20,

            "mocRegulatorja": 1,
            "steviloRegulatorjev": 1
        }
        EOT;

        $sistem = new \App\Calc\GF\TSS\OHTSistemi\ToplovodniOHTSistem();
        $sistem->ogrevanje = new \App\Calc\GF\TSS\OHTSistemi\Sistemi\Ogrevanje();
        $sistem->ogrevanje->rezim = \App\Calc\GF\TSS\OHTSistemi\Izbire\VrstaRezima::Rezim_40_30;

        $koncniPrenosnik = new Konvektor(json_decode($config));

        $this->assertEquals(0.2, $koncniPrenosnik->deltaT_hydr);
        $this->assertEquals(0.0, $koncniPrenosnik->deltaT_str);
        $this->assertEquals(0.7, $koncniPrenosnik->deltaT_ctr);
        $this->assertEquals(0.0, $koncniPrenosnik->deltaT_emb);
        $this->assertEquals(0.0, $koncniPrenosnik->deltaT_sol);
        $this->assertEquals(0.0, $koncniPrenosnik->deltaT_im);

        $preneseneIzgube = [1206.707, 746.368, 390.117, 135.734, 19.220, 0.000, 0.000, 0.000, 17.903, 179.496, 761.644, 1208.785];

        $izgube = $koncniPrenosnik->toplotneIzgube($preneseneIzgube, $sistem, $cona, $okolje, ['namen' => 'ogrevanje']);

        $roundedResult = array_map(fn($el) => round($el, 2), $izgube['ogrevanje']);
        $expected = [51.72, 35.35, 23.41, 11.11, 2.88, 0.00, 0.00, 0.00, 3.22, 16.15, 42.84, 54.40];
        $this->assertEquals($expected, $roundedResult);
    }
}
