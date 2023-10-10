<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class IzracunKonstrukcijeTSG004Test extends TestCase
{
    public function testValidacijaTSG(): void
    {
        $inputZunanjaT = [-1, 1, 6, 10, 15, 18, 20, 19, 15, 10, 4, 1];
        $inputZunanjaVlaga = [82 , 77, 72, 71, 73, 72, 75, 76, 80, 82, 84, 85];
        $okolje = \App\Lib\CalcOkolje::notranjeOkolje(['zunanjaT' => $inputZunanjaT, 'zunanjaVlaga' => $inputZunanjaVlaga]);

        $expectedInternalTemp = [20, 20, 20, 20, 22.5, 24, 25, 24.5, 22.5, 20, 20, 20];
        $this->assertEquals($expectedInternalTemp, $okolje->notranjaT);
        $expectedInternalHum = [44, 46, 51, 55, 60, 63, 65, 64, 60, 55, 49, 46];
        $this->assertEquals($expectedInternalHum, $okolje->notranjaVlaga);
        $roundedResult = array_map(fn($el) => round($el, 3), $okolje->minfRsi);
        $expected = [0.557, 0.545, 0.495, 0.409, 0.380, 0.347, 0.312, 0.331, 0.380, 0.409, 0.520, 0.545];
        $this->assertEquals($expected, $roundedResult);

        $konstrukcijaJson = <<<EOT
        {
            "id": "Z1",
            "naziv": "Zunanji zid 1",
            "vrsta": 1,
            "Rsi": 0.25,
            "Rse": 0.04,
            "materiali": [
                {
                    "opis": "a",
                    "debelina": 0.15,
                    "lambda": 2.04,
                    "gostota": 2400,
                    "difuzijskaUpornost": 9
                },
                {
                    "opis": "b",
                    "debelina": 0.15,
                    "lambda": 0.041,
                    "gostota": 20,
                    "difuzijskaUpornost": 5.25
                },
                {
                    "opis": "c",
                    "debelina": 0.15,
                    "lambda": 2.04,
                    "gostota": 2400,
                    "difuzijskaUpornost": 9
                }
            ]
        }
        EOT;
        $konstrukcija = json_decode($konstrukcijaJson);
        $result = \App\Lib\CalcKonstrukcije::konstrukcija($konstrukcija, $okolje, ['izracunKondenzacije' => true]);

        $roundedResult = array_map(fn($el) => round($el, 1), $result->Tsi);
        $expectedTsi = [18.7, 18.8, 19.1, 19.4, 22, 23.6, 24.7, 24.2, 22.0, 19.4, 19.0, 18.8];
        $this->assertEquals($expectedTsi, $roundedResult);

        $roundedResult = array_map(fn($el) => round($el, 3), $result->fRsi);
        $expected_fRsi = [0.939, 0.939, 0.939, 0.939, 0.939, 0.939, 0.939, 0.939, 0.939, 0.939, 0.939, 0.939];
        $this->assertEquals($expected_fRsi, $roundedResult);

        $roundedResult = array_map(fn($el) => round($el, 1), $result->gc);
        $expectedGs = [9, 4, -8, -21, 0, 0, 0, 0, 0, 0, 2, 7];
        //$this->assertEquals($expectedGs, $roundedResult);
    }

    public function testValidacijaTSGProtiZemlji(): void
    {
        $elementOvojaJson = <<<EOT
        {
            "tla": "pesek",
            "protiZraku": true,
            "barva": "brez",
            "obseg": 200,
            "povrsina": 550,
            "debelinaStene": 0.5,
            "vertPsi": 0.1,
            "konstrukcija": {
                "id": "T1",
                "TSG": {"tip": "tla-teren"},
                "Rsi": 0.17,
                "Rse": 0,
                "U": 0.460829493087557603
            }
        }
        EOT;

        $okolje = new \stdClass();
        $okolje->povprecnaLetnaTemp = 9.9;
        $okolje->zunanjaT = [5, 6, 9, 12, 17, 20, 23, 23, 19, 14, 9, 6];
        $okolje->notranjaT = [20, 20, 20, 20, 20, 26, 26, 26, 26, 20, 20, 20];

        $cona = new \stdClass();
        $cona->notranjaTHlajenje = 26;
        $cona->notranjaTOgrevanje = 20;

        $config = json_decode($elementOvojaJson);
        $element = new App\Calc\GF\Cone\ElementiOvoja\NetransparentenElementOvoja(null, $config);
        $element->analiza($cona, $okolje);

        $this->assertEquals(165.8, round($element->Lpi, 1));
        $this->assertEquals(75.1, round($element->Lpe, 1));

        //var_dump($element->transIzgubeOgrevanje);
    }
}
