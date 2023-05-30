<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Calc\TSS\KoncniPrenosniki\PloskovnoOgrevalo;

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

            "mocRegulatorja": 1,
            "steviloRegulatorjev": 1
        }
        EOT;

        $sistem = new PloskovnoOgrevalo($config);

        $preneseneIzgube = [1206.707, 746.368, 390.117, 135.734, 19.220, 0.000, 0.000, 0.000, 17.903, 179.496, 761.644, 1208.785];

        $izgube = $sistem->toplotneIzgube($preneseneIzgube, null, $cona, $okolje);
        $roundedResult = array_map(fn($el) => round($el, 2), $izgube);

        $expected = [109.18, 74.64, 49.41, 23.44, 6.09, 0.00, 0.00, 0.00, 6.80, 34.10, 90.45, 114.83];

        $this->assertEquals($expected, $roundedResult);
    }
}