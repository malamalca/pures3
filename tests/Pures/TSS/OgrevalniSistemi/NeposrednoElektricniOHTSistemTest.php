<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\OgrevalniSistemi;

use App\Calc\GF\TSS\OgrevalniSistemi\NeposredniElektricniOHTSistem;
use PHPUnit\Framework\TestCase;

final class NeposrednoElektricniOHTSistemTest extends TestCase
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
            "id": "IRPaneli",
            "idCone": "Cona1",
            "vrsta": "neposrednoElektricni",
            "nazivnaMoc": 6,
            "izkoristek": 0.9,
            "prenosniki": [{
                "id": "IRPanel",
                "vrsta": "elektricnoOgrevalo",
                "regulacija": "P-krmilnik",
                "prezracevanaCona": false,
                "mocRegulatorja": 1,
                "steviloRegulatorjev": 3
            }]
        }
        EOT;

        $sistem = new NeposredniElektricniOHTSistem($config);

        $sistem->analiza($cona, $okolje);

        $izgubePrenosnikov = $sistem->koncniPrenosniki[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubePrenosnikov);
        $expected = [89.54, 61.89, 41.88, 20.31, 5.42, 0.00, 0.00, 0.00, 6.17, 29.94, 75.39, 94.22];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->koncniPrenosniki[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [2.23, 2.02, 2.23, 1.78, 0.26, 0.00, 0.00, 0.00, 0.25, 2.23, 2.16, 2.23];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaEnergija = $sistem->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaEnergija);
        $expected = [1492.36, 939.74, 511.91, 188.06, 30.12, 0.00, 0.00, 0.00, 29.69, 255.08, 977.34, 1500.58];
        $this->assertEquals($expected, $roundedResult);
    }
}
