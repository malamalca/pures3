<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\KoncniPrenosniki;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Hlajenje\StenskiKonvektor;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Hlajenje\StropniKonvektor;
use PHPUnit\Framework\TestCase;

final class HladilniKonvektorTest extends TestCase
{
    public function testStenskiKonvektor(): void
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
            "id": "HLADILNIKONEVKTOR",
            "vrsta": "hladilniKonvektor",

            "stevilo": 2,

            "hidravlicnoUravnotezenje": "staticnoDviznihVodov",
            "regulacijaTemperature": "referencniProstor",

            "mocAux": 10
        }
        EOT;

        $generator = new \stdClass();
        $generator->id = 'KOMPRESOR';
        $generator->nazivnaMoc = 6;

        $sistem = new \App\Calc\GF\TSS\OHTSistemi\HladilniSistemSHladnoVodo();
        $sistem->generatorji = [$generator];
        $sistem->povprecnaObremenitev = [0, 0, 0, 0, 0, 0, 0.002083, 0.002083, 0, 0, 0, 0];

        $koncniPrenosnik = new StenskiKonvektor(json_decode($config));

        $this->assertEquals(-0.2, $koncniPrenosnik->deltaT_hydr);
        $this->assertEquals(-1.8, $koncniPrenosnik->deltaT_ctr);
        $this->assertEquals(-0.4, $koncniPrenosnik->deltaT_str);
        $this->assertEquals(0.0, $koncniPrenosnik->deltaT_emb);
        $this->assertEquals(12.0, $koncniPrenosnik->deltaT_sol);
        $this->assertEquals(-0.3, $koncniPrenosnik->deltaT_im);

        $letneZahteveC = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 36.05, 37.18, 0.00, 0.00, 0.00, 0.00];

        $izgube = $koncniPrenosnik->toplotneIzgube($letneZahteveC, $sistem, $cona, $okolje, ['namen' => 'hlajenje']);
        $roundedResult = array_map(fn($el) => round($el, 2), $izgube);

        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 19.47, 20.08, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);

        $elektricneIzgube = $koncniPrenosnik->potrebnaElektricnaEnergija($letneZahteveC, $sistem, $cona, $okolje, ['namen' => 'hlajenje']);
        $roundedResult = array_map(fn($el) => round($el, 2), $elektricneIzgube);

        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.31, 0.31, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);
    }

    public function testStropniKonvektor(): void
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
            "id": "HLADILNIKONEVKTOR",
            "vrsta": "hladilniKonvektor",

            "stevilo": 2,

            "hidravlicnoUravnotezenje": "dinamicnoDelnaObremenitev",
            "regulacijaTemperature": "PI-krmilnik",

            "mocAux": 10
        }
        EOT;

        $generator = new \stdClass();
        $generator->id = 'KOMPRESOR';
        $generator->nazivnaMoc = 6;

        $sistem = new \App\Calc\GF\TSS\OHTSistemi\HladilniSistemSHladnoVodo();
        $sistem->generatorji = [$generator];
        $sistem->povprecnaObremenitev = [0, 0, 0, 0, 0, 0, 0.002083, 0.002083, 0, 0, 0, 0];

        $koncniPrenosnik = new StropniKonvektor(json_decode($config));

        $this->assertEquals(0.0, $koncniPrenosnik->deltaT_hydr);
        $this->assertEquals(-0.7, $koncniPrenosnik->deltaT_ctr);
        $this->assertEquals(0.0, $koncniPrenosnik->deltaT_str);
        $this->assertEquals(0.0, $koncniPrenosnik->deltaT_emb);
        $this->assertEquals(12.0, $koncniPrenosnik->deltaT_sol);
        $this->assertEquals(-0.3, $koncniPrenosnik->deltaT_im);

        $letneZahteveC = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 36.05, 37.18, 0.00, 0.00, 0.00, 0.00];

        $izgube = $koncniPrenosnik->toplotneIzgube($letneZahteveC, $sistem, $cona, $okolje, ['namen' => 'hlajenje']);
        $roundedResult = array_map(fn($el) => round($el, 2), $izgube);

        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 7.21, 7.44, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);

        $elektricneIzgube = $koncniPrenosnik->potrebnaElektricnaEnergija($letneZahteveC, $sistem, $cona, $okolje, ['namen' => 'hlajenje']);
        $roundedResult = array_map(fn($el) => round($el, 2), $elektricneIzgube);

        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.31, 0.31, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);
    }
}
