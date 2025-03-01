<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\KoncniPrenosniki;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\PloskovnoOgrevalo;
use PHPUnit\Framework\TestCase;

final class PloskovnoOgrevaloTest extends TestCase
{
    public function testToplotneIzgube(): void
    {
        $okolje = new \stdClass();
        $okolje->zunanjaT = [ -1, 1, 5, 9, 14, 17, 19, 19, 15, 10, 4, 0];

        $cona = new \stdClass();
        $cona->notranjaTOgrevanje = 20;

        $config = <<<EOT
        {
            "id": "TALNO",
            "vrsta": "ploskovnaOgrevala",

            "sistem": "talno_mokri",
            "izolacija": "100%",

            "hidravlicnoUravnotezenje": "staticnoDviznihVodov",
            "regulacijaTemperature": "referencniProstor",

            "stevilo": 1,

            "mocRegulatorja": 1,
            "steviloRegulatorjev": 1
        }
        EOT;

        $koncniPrenosnik = new PloskovnoOgrevalo(json_decode($config));

        $this->assertEquals(0.2, $koncniPrenosnik->deltaT_hydr);
        $this->assertEquals(1.6, $koncniPrenosnik->deltaT_ctr);
        $this->assertEquals(0.0, $koncniPrenosnik->deltaT_str);
        $this->assertEquals(0.05, $koncniPrenosnik->deltaT_emb);
        $this->assertEquals(0.0, $koncniPrenosnik->deltaT_sol);
        $this->assertEquals(0.0, $koncniPrenosnik->deltaT_im);

        $preneseneIzgube = [1206.707, 746.368, 390.117, 135.734, 19.220, 0.000, 0.000, 0.000, 17.903, 179.496, 761.644, 1208.785];

        $izgube = $koncniPrenosnik->toplotneIzgube($preneseneIzgube, null, $cona, $okolje, ['namen' => 'ogrevanje']);
        $roundedResult = array_map(fn($el) => round($el, 2), $izgube['ogrevanje']);

        $expected = [106.31, 72.67, 48.11, 22.83, 5.93, 0.00, 0.00, 0.00, 6.62, 33.21, 88.07, 111.81];

        $this->assertEquals($expected, $roundedResult);
    }
}
