<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Calc\TSS\OgrevalniSistemi\LokalniOgrevalniSistemNaBiomaso;

final class LokalniOgrevalniSistemNaBiomasoTest extends TestCase
{
    public function testToplotneIzgube(): void
    {
        /** @var array $coneIn */
        $coneIn = json_decode(file_get_contents(PROJECTS . 'TestniProjekt' . DS . 'izracuni' . DS . 'cone.json'));
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

        $sistem = new LokalniOgrevalniSistemNaBiomaso($config);

        $sistem->analiza($cona, $okolje);

        $izgubePrenosnikov = $sistem->koncniPrenosniki[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubePrenosnikov);
        $expected = [240.06, 167.40, 115.39, 57.02, 15.61, 0.00, 0.00, 0.00, 18.01, 84.96, 204.77, 252.69];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->koncniPrenosniki[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [2.23, 2.02, 2.23, 1.92, 0.29, 0.00, 0.00, 0.00, 0.28, 2.23, 2.16, 2.23];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaEnergija = $sistem->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaEnergija);
        $expected = [1576.85, 1011.28, 575.32, 224.13, 40.75, 0.00, 0.00, 0.00, 42.29, 311.51, 1074.94, 1593.13];
        $this->assertEquals($expected, $roundedResult);
    }
}