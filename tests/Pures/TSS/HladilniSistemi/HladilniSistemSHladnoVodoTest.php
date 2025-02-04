<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\OHTSistemi;

use App\Calc\GF\TSS\OHTSistemi\HladilniSistemSHladnoVodo;
use PHPUnit\Framework\TestCase;

final class HladilniSistemSHladnoVodoTest extends TestCase
{
    public function test1(): void
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
            "id": "HLA",
            "idCone": "Cona1",
            "vrsta": "ohlajenaVoda",
            "tip": "zracnoHlajen",
            "energent": "elektrika",
            
            "hlajenje": {
                "generatorji": ["KOMPRESOR"],
                "prenosniki": ["STENSKIKONVEKTORJI"],
                "razvodi": ["RAZVODHLAJENJA"]
            },

            "generatorji": [
                {
                    "id": "KOMPRESOR",
                    "vrsta": "hladilniKompresor",
                    "tip": "batni",
                    "vrstaRegulacije": "vecstopenjsko",
                    "nazivnaMoc": 6,
                    "EER": 4
                }
            ],

            "razvodi": [
                {
                    "id": "RAZVODHLAJENJA",
                    "vrsta": "hlajenje",
                    "idPrenosnika": "STENSKIKONVEKTORJI",
                    "crpalka": {
                        "moc": 12
                    }
                }
            ],

            "prenosniki": [
                {
                    "id": "STENSKIKONVEKTORJI",
                    "vrsta": "hladilniStenskiKonvektor",
    
                    "hidravlicnoUravnotezenje": "staticnoDviznihVodov",
                    "regulacijaTemperature": "referencniProstor",
    
                    "mocRegulatorja": 2,
                    "steviloRegulatorjev": 1,
                    "mocAux": 8
                }
            ]
        }
        EOT;

        $sistem = new HladilniSistemSHladnoVodo($config);

        $sistem->analiza($cona, $okolje);
        $koncniPrenosnik = $sistem->koncniPrenosniki[0];
        $razvod = $sistem->razvodi[0];
        $generator = $sistem->generatorji[0];

        $this->assertEquals(-0.2, $koncniPrenosnik->deltaT_hydr);
        $this->assertEquals(-0.4, $koncniPrenosnik->deltaT_str);
        $this->assertEquals(-1.8, $koncniPrenosnik->deltaT_ctr);
        $this->assertEquals(0.0, $koncniPrenosnik->deltaT_emb);
        $this->assertEquals(12.0, $koncniPrenosnik->deltaT_sol);
        $this->assertEquals(-0.3, $koncniPrenosnik->deltaT_im);

        // preverimo konvektor
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 19.47, 20.08, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, array_map(fn($el) => round($el, 2), $koncniPrenosnik->toplotneIzgube['hlajenje']));

        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.25 + 0.06, 0.25 + 0.06, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, array_map(fn($el) => round($el, 2), $koncniPrenosnik->potrebnaElektricnaEnergija['hlajenje']));

        // preverimo razvod
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2.78, 2.86, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, array_map(fn($el) => round($el, 2), $razvod->toplotneIzgube['hlajenje']));

        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.37, 0.37, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, array_map(fn($el) => round($el, 2), $razvod->potrebnaElektricnaEnergija['hlajenje']));

        // preverimo generator
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, array_map(fn($el) => round($el, 2), $generator->toplotneIzgube['hlajenje']));

        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, array_map(fn($el) => round($el, 2), $generator->potrebnaElektricnaEnergija['hlajenje']));

        // sistem
        //$expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3.82, 3.92, 0.00, 0.00, 0.00, 0.00];
        //$this->assertEquals($expected, array_map(fn($el) => round($el, 2), $sistem->toplotneIzgube['elektrika']));
    }
}
