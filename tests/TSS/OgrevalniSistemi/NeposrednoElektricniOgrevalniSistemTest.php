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
        $expected = [67.60, 47.12, 32.44, 16.02, 4.40, 0.00, 0.00, 0.00, 5.08, 23.85, 57.62, 71.15];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->koncniPrenosniki[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [2.23, 2.02, 2.23, 1.92, 0.29, 0.00, 0.00, 0.00, 0.28, 2.23, 2.16, 2.23];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaEnergija = $sistem->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaEnergija);
        $expected = [1508.99, 956.59, 527.63, 195.84, 31.54, 0.00, 0.00, 0.00, 31.27, 267.39, 995.24, 1516.47];
        $this->assertEquals($expected, $roundedResult);
    }
}