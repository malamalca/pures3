<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Calc\GF\TSS\OgrevalniSistemi\NeposredniElektricniOgrevalniSistem;

final class NeposrednoElektricniOgrevalniSistemTest extends TestCase
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

        $sistem = new NeposredniElektricniOgrevalniSistem($config);

        $sistem->analiza($cona, $okolje);

        $izgubePrenosnikov = $sistem->koncniPrenosniki[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubePrenosnikov);
        $expected = [65.55, 45.28, 30.62, 14.84, 3.97, 0.00, 0.00, 0.00, 4.52, 21.85, 55.16, 68.98];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->koncniPrenosniki[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [2.23, 2.02, 2.23, 1.77, 0.26, 0.00, 0.00, 0.00, 0.25, 2.23, 2.16, 2.23];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaEnergija = $sistem->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaEnergija);
        $expected = [1463.34, 919.39, 497.91, 181.41, 28.5, 0.00, 0.00, 0.00, 27.84, 245.04, 952.69, 1470.14];
        $this->assertEquals($expected, $roundedResult);
    }
}