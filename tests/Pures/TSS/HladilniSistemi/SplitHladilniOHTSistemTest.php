<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\OHTSistemi;

use App\Calc\GF\TSS\OHTSistemi\SplitHladilniOHTSistem;
use PHPUnit\Framework\TestCase;

final class SplitHladilniOHTSistemTest extends TestCase
{
    public function testSplitHlajenje(): void
    {
        /** @var array $coneIn */
        $coneIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'cone.json'));
        $cona = $coneIn[0];

        $okolje = new \stdClass();
        $okolje->projektnaZunanjaT = -13;
        $okolje->zunanjaT = [-1, 1, 5, 9, 14, 17, 19, 19, 15, 10, 4, 0];
        $okolje->zunanjaVlaga = [82, 78, 74, 72, 73, 74, 75, 77, 81, 83, 85, 85];

        $config = <<<EOT
        {
            "vrsta": "splitHlajenje",
            "idCone": "Cona1",
            "regulacija": "prilagodljivoDelovanje",
            "nazivnaMoc": 3.5,
            "EER": 4
        }
        EOT;

        $sistem = new SplitHladilniOHTSistem(json_decode($config));

        $sistem->analiza($cona, $okolje);

        $expected = [0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0];
        $this->assertEquals($expected, $sistem->generatorji[0]->stUrDelovanjaNaDan);

        $roundedResult = array_map(fn($el) => round($el, 2), $sistem->generatorji[0]->korekcijskiFaktorEER);
        $expected = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.28, 1.28, 0.0, 0.0, 0.0, 0.0];
        $this->assertEquals($expected, $roundedResult);

        $roundedResult = array_map(fn($el) => round($el, 2), $sistem->generatorji[0]->toplotneIzgube['hlajenje']);
        $expected = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 4.49, 4.63, 0.0, 0.0, 0.0, 0.0];
        $this->assertEquals($expected, $roundedResult);

        $config = <<<EOT
        {
            "vrsta": "splitHlajenje",
            "idCone": "Cona1",
            "regulacija": "OnOff",
            "nazivnaMoc": 3.5,
            "EER": 4
        }
        EOT;

        $sistem = new SplitHladilniOHTSistem(json_decode($config));

        $sistem->analiza($cona, $okolje);

        $expected = [0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0];
        $this->assertEquals($expected, $sistem->generatorji[0]->stUrDelovanjaNaDan);

        $roundedResult = array_map(fn($el) => round($el, 2), $sistem->generatorji[0]->korekcijskiFaktorEER);
        $expected = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.28, 1.28, 0.0, 0.0, 0.0, 0.0];
        $this->assertEquals($expected, $roundedResult);

        $roundedResult = array_map(fn($el) => round($el, 2), $sistem->generatorji[0]->toplotneIzgube['hlajenje']);
        $expected = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 5.26, 5.43, 0.0, 0.0, 0.0, 0.0];
        $this->assertEquals($expected, $roundedResult);
    }

    public function testMultiSplitHlajenje(): void
    {
        /** @var array $coneIn */
        $coneIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'cone.json'));
        $cona = $coneIn[0];

        $okolje = new \stdClass();
        $okolje->projektnaZunanjaT = -13;
        $okolje->zunanjaT = [-1, 1, 5, 9, 14, 17, 19, 19, 15, 10, 4, 0];
        $okolje->zunanjaVlaga = [82, 78, 74, 72, 73, 74, 75, 77, 81, 83, 85, 85];

        $config = <<<EOT
        {
            "vrsta": "splitHlajenje",
            "idCone": "Cona1",
            "multiSplit": true,
            "regulacija": "prilagodljivoDelovanje",
            "nazivnaMoc": 3.5,
            "EER": 4
        }
        EOT;

        $sistem = new SplitHladilniOHTSistem(json_decode($config));

        $sistem->analiza($cona, $okolje);

        $expected = [0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0];
        $this->assertEquals($expected, $sistem->generatorji[0]->stUrDelovanjaNaDan);

        $roundedResult = array_map(fn($el) => round($el, 2), $sistem->generatorji[0]->korekcijskiFaktorEER);
        $expected = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.28, 1.28, 0.0, 0.0, 0.0, 0.0];
        $this->assertEquals($expected, $roundedResult);

        $roundedResult = array_map(fn($el) => round($el, 2), $sistem->generatorji[0]->toplotneIzgube['hlajenje']);
        $expected = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 4.97, 5.12, 0.0, 0.0, 0.0, 0.0];
        $this->assertEquals($expected, $roundedResult);

        $config = <<<EOT
        {
            "vrsta": "splitHlajenje",
            "idCone": "Cona1",
            "multiSplit": true,
            "regulacija": "OnOff",
            "nazivnaMoc": 3.5,
            "EER": 4
        }
        EOT;

        $sistem = new SplitHladilniOHTSistem(json_decode($config));

        $sistem->analiza($cona, $okolje);

        $expected = [0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0];
        $this->assertEquals($expected, $sistem->generatorji[0]->stUrDelovanjaNaDan);

        $roundedResult = array_map(fn($el) => round($el, 2), $sistem->generatorji[0]->korekcijskiFaktorEER);
        $expected = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.28, 1.28, 0.0, 0.0, 0.0, 0.0];
        $this->assertEquals($expected, $roundedResult);

        $roundedResult = array_map(fn($el) => round($el, 2), $sistem->generatorji[0]->toplotneIzgube['hlajenje']);
        $expected = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 9.16, 9.45, 0.0, 0.0, 0.0, 0.0];
        $this->assertEquals($expected, $roundedResult);
    }
}
