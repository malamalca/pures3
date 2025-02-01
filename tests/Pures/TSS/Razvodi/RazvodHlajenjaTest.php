<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\Razvodi;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\RazvodHlajenja;
use PHPUnit\Framework\TestCase;

final class RazvodHlajenjaTest extends TestCase
{
    public function testIzgube(): void
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
            "id": "RAZVODHLAJENJA",
            "idPrenosnika": "KONVEKTOR",
            "crpalka": {
                "moc": 30
            }
        }
        EOT;

        $prenosnik = new \stdClass();
        $prenosnik->id = 'KONVEKTOR';
        $prenosnik->toplotneIzgube = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 19.47, 20.08, 0.00, 0.00, 0.00, 0.00];

        $generator = new \stdClass();
        $generator->id = 'KOMPRESOR';
        $generator->nazivnaMoc = 6;

        $sistem = new \stdClass();
        $sistem->koncniPrenosniki = [$prenosnik];
        $sistem->generatorji = [$generator];

        $letneZahteveC = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 36.05, 37.18, 0.00, 0.00, 0.00, 0.00];

        $razvodHlajenja = new RazvodHlajenja(json_decode($config));
        $izgube = $razvodHlajenja->toplotneIzgube($letneZahteveC, $sistem, $cona, $okolje);
        $roundedResult = array_map(fn($el) => round($el, 2), $izgube);

        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2.78, 2.86, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);

        $elektricneIzgube = $razvodHlajenja->potrebnaElektricnaEnergija($letneZahteveC, $sistem, $cona, $okolje);
        $roundedResult = array_map(fn($el) => round($el, 2), $elektricneIzgube);

        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.93, 0.93, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);
    }
}
