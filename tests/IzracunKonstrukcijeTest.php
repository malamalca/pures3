<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class IzracunKonstrukcijeTest extends TestCase
{
    public function testValidacijaTSG(): void
    {
        $inputZunanjaT = [-1, 1, 6, 10, 15, 18, 20, 19, 15, 10, 4, 1];
        $inputZunanjaVlaga = [82 , 77, 72, 71, 73, 72, 75, 76, 80, 82, 84, 85];
        $okolje = \App\Lib\CalcOkolje::notranjeOkolje(['zunanjaT' => $inputZunanjaT, 'zunanjaVlaga' => $inputZunanjaVlaga]);
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
        $result = \App\Lib\CalcKonstrukcije::konstrukcija($konstrukcija, $okolje);
        $roundedResult = array_map(fn($el) => round($el, 1), $result->Tsi);
        $expectedTsi = [18.7, 18.8, 19.1, 19.4, 22, 23.6, 24.7, 24.2, 22.0, 19.4, 19.0, 18.8];
        $this->assertEquals($expectedTsi, $roundedResult);
        $roundedResult = array_map(fn($el) => round($el, 3), $result->fRsi);
        $expected_fRsi = [0.939, 0.939, 0.939, 0.939, 0.939, 0.939, 0.939, 0.939, 0.939, 0.939, 0.939, 0.939];
        $this->assertEquals($expected_fRsi, $roundedResult);
    }
}
