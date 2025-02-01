<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\Generatorji;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\HladilniKompresor;
use PHPUnit\Framework\TestCase;

final class HladilniKompresorTest extends TestCase
{
    public function testPotrebnaEnergijaV1(): void
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
            "id": "HLADILNIKOMPRESOR",

            "vrstaHlajenja": "zracno",
            "vrstaKompresorja": "batni",
            "vrstaRegulacije": "onOff",
            "regulacijaHladilneMoci": true,

            "nazivnaMoc": 6,
            "EER": 4,

            "TizhodnegaZraka": 19,

            "mocRegulatorja": 5,
            "steviloRegulatorjev": 1
        }
        EOT;

        $letneZahteve = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 36.05+19.47+2.78, 37.18+20.08+2.86, 0.00, 0.00, 0.00, 0.00];

        $generator = new HladilniKompresor(json_decode($config));

        $izgube = $generator->toplotneIzgube($letneZahteve, null, $cona, $okolje);
        $this->assertEquals([0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0], $generator->stUrDelovanjaNaDan);


        $expected = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 2.87, 2.87, 0.0, 0.0, 0.0, 0.0];
        $this->assertEquals($expected, array_map(fn($el) => round($el, 2), $generator->korekcijskiFaktorEER));

        // E_C
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3.98, 4.10, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, array_map(fn($el) => round($el, 2), $izgube['hlajenje']));

        // Wctr,el,in
        $elektricneIzgube = $generator->potrebnaElektricnaEnergija($letneZahteve, null, $cona, $okolje);
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.16, 0.16, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, array_map(fn($el) => round($el, 2), $elektricneIzgube));
    }

    public function testPotrebnaEnergijaV2(): void
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
            "id": "HLADILNIKOMPRESOR",

            "vrstaHlajenja": "zracno",
            "vrstaKompresorja": "turbinski",
            "vrstaRegulacije": "prilagodljivo",
            "regulacijaHladilneMoci": true,
            "kondenzatorVKanalu": true,

            "nazivnaMoc": 6,
            "EER": 4,

            "TizhodnegaZraka": 19,

            "mocRegulatorja": 5,
            "steviloRegulatorjev": 1
        }
        EOT;

        $letneZahteve = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 36.05+19.47+2.78, 37.18+20.08+2.86, 0.00, 0.00, 0.00, 0.00];

        $generator = new HladilniKompresor(json_decode($config));

        $izgube = $generator->toplotneIzgube($letneZahteve, null, $cona, $okolje);
        $this->assertEquals([0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0], $generator->stUrDelovanjaNaDan);


        $expected = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.71, 1.71, 0.0, 0.0, 0.0, 0.0];
        $this->assertEquals($expected, array_map(fn($el) => round($el, 2), $generator->korekcijskiFaktorEER));

        // E_C
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4.87, 5.02, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, array_map(fn($el) => round($el, 2), $izgube['hlajenje']));

        // Wctr,el,in
        $elektricneIzgube = $generator->potrebnaElektricnaEnergija($letneZahteve, null, $cona, $okolje);
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.16, 0.16, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, array_map(fn($el) => round($el, 2), $elektricneIzgube));
    }
}
