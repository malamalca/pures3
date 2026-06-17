<?php
declare(strict_types=1);

namespace App\Test\Pures;

use App\Calc\GF\Cone\ElementiOvoja\NetransparentenElementOvoja;
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
                    "difuzijskaUpornost": 60
                },
                {
                    "opis": "b",
                    "debelina": 0.15,
                    "lambda": 0.041,
                    "gostota": 20,
                    "difuzijskaUpornost": 35
                },
                {
                    "opis": "c",
                    "debelina": 0.15,
                    "lambda": 2.04,
                    "gostota": 2400,
                    "difuzijskaUpornost": 60
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

        // TSG tabela 8.3: mesečna količina nastalega/izsušenega kondenzata gc (g/(m2 m)).
        // Vhodna difuzijska upornost je μ = sd / d (TSG navaja sd = 9/5,25/9 m, debeline 0,15 m -> μ = 60/35/60).
        // gc se izračuna le za mesece s kondenzacijo/izsuševanjem; ostale mesece (poletje) izenačimo z 0.
        $gc = [];
        foreach (range(0, 11) as $mesec) {
            $gc[$mesec] = (int)round($result->gc[$mesec] ?? 0, 0);
        }
        $expectedGc = [9, 4, -8, -21, 0, 0, 0, 0, 0, 0, 2, 7];
        $this->assertEquals($expectedGc, $gc);
    }

    /**
     * TSG-1-004:2022, tabela 8.4 - kontrolni primer: U = 0,130 W/(m2K), faktor toplotne stabilnosti f = 0,449
     * (dušilni/dekrementni faktor po SIST EN ISO 13786, točka 8.1.5).
     */
    public function testValidacijaTSG84(): void
    {
        $inputZunanjaT = [-1, 1, 6, 10, 15, 18, 20, 19, 15, 10, 4, 1];
        $inputZunanjaVlaga = [82, 77, 72, 71, 73, 72, 75, 76, 80, 82, 84, 85];
        $okolje = \App\Lib\CalcOkolje::notranjeOkolje(['zunanjaT' => $inputZunanjaT, 'zunanjaVlaga' => $inputZunanjaVlaga]);

        $konstrukcijaJson = <<<EOT
        {
            "id": "S1",
            "naziv": "Streha",
            "vrsta": 1,
            "Rsi": 0.13,
            "Rse": 0.04,
            "materiali": [
                {"opis": "a", "debelina": 0.03, "lambda": 0.13, "gostota": 700, "specificnaToplota": 2100},
                {"opis": "b", "debelina": 0.25, "lambda": 0.035, "gostota": 100, "specificnaToplota": 840},
                {"opis": "c", "debelina": 0.04, "lambda": 0.21, "gostota": 900, "specificnaToplota": 840}
            ]
        }
        EOT;
        $konstrukcija = json_decode($konstrukcijaJson);
        $result = \App\Lib\CalcKonstrukcije::konstrukcija($konstrukcija, $okolje);

        // U = 0,130 W/(m2K) (TSG tabela 8.4)
        $this->assertEqualsWithDelta(0.130, $result->U, 0.001);

        // faktor toplotne stabilnosti f = 0,449 (TSG tabela 8.4, SIST EN ISO 13786)
        $this->assertEqualsWithDelta(0.449, $result->f, 0.005);
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
            "obodniPsi": 0.1,
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
        $element = new NetransparentenElementOvoja(null, $config);
        $element->analiza($cona, $okolje);

        $this->assertEquals(165.8, round($element->Lpi, 1));
        $this->assertEquals(75.1, round($element->Lpe, 1));

        //var_dump($element->transIzgubeOgrevanje);
    }
}
