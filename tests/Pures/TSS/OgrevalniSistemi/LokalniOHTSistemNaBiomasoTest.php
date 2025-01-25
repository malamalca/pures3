<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\OHTSistemi;

use App\Calc\GF\TSS\OHTSistemi\LokalniOHTSistemNaBiomaso;
use PHPUnit\Framework\TestCase;

final class LokalniOHTSistemNaBiomasoTest extends TestCase
{
    public function testToplotneIzgube(): void
    {
        /** @var array $coneIn */
        $coneIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'cone.json'));
        $cona = $coneIn[0];

        $okolje = new \stdClass();
        $okolje->projektnaZunanjaT = -13;
        $okolje->zunanjaT = [-1, 1, 5, 9, 14, 17, 19, 19, 15, 10, 4, 0];

        $config = <<<EOT
        {
            "id": "lokalniBiomasa",
            "idCone": "Cona1",
            "vrsta": "lokalniBiomasa",
            "nazivnaMoc": 6,
            "izkoristek": 0.9,
            "prenosniki": [{
                "id": "kamin",
                "vrsta": "pecNaDrva",
                "regulacija": "termostatNaPeci",
                "prezracevanaCona": false,
                "mocRegulatorja": 1,
                "steviloRegulatorjev": 3
            }]
        }
        EOT;

        $sistem = new LokalniOHTSistemNaBiomaso($config);

        $sistem->analiza($cona, $okolje);

        $izgubePrenosnikov = $sistem->koncniPrenosniki[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubePrenosnikov);
        $expected = [232.81, 160.90, 108.90, 52.81, 14.10, 0.00, 0.00, 0.00, 16.03, 77.86, 196.03, 244.98];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->koncniPrenosniki[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [2.23, 2.02, 2.23, 1.78, 0.26, 0.00, 0.00, 0.00, 0.25, 2.23, 2.16, 2.23];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaEnergija = $sistem->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaEnergija);
        $expected = [1529.21, 972.00, 542.94, 207.56, 36.82, 0.00, 0.00, 0.00, 37.64, 285.48, 1029.05, 1544.53];
        $this->assertEquals($expected, $roundedResult);
    }
}
