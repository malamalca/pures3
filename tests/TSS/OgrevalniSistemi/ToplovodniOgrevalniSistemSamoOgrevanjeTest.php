<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Calc\TSS\OgrevalniSistemi\ToplovodniOgrevalniSistem;

final class ToplovodniOgrevalniSistemSamoOgrevanjeTest extends TestCase
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
            "id": "TC",
            "idCone": "Cona1",
            "vrsta": "toplovodni",
            "energent": "elektrika",
            
            "ogrevanje": {
                "rezim": "40/30",
                "generatorji": ["TC"],
                "razvodi": ["Ogrevanje"],
                "prenosniki": ["Talno"]
            },
    
            "generatorji": [
                {
                    "id": "TC",
                    "vrsta": "TC_zrakvoda",
                    "podnebje": "celinsko",
                    "nazivnaMoc": 6,
                    "nazivniCOP": 3,
                    "elektricnaMocNaPrimarnemKrogu": 6,
                    "elektricnaMocNaSekundarnemKrogu": 3
                }
            ],
    
            "razvodi": [
                {
                    "vrsta": "dvocevni",
                    "id": "Ogrevanje",
                    "idPrenosnika": "Talno",
                    "crpalka": {},
                    "ceviHorizontaliVodi": {
                        "delezVOgrevaniConi": 0.8
                    },
                    "ceviDvizniVodi": {
                        "delezVOgrevaniConi": 0.8
                    },
                    "ceviPrikljucniVodi": {
                    }
                }
            ],

            "prenosniki": [
                {
                    "id": "Talno",
                    "vrsta": "ploskovnaOgrevala",
    
                    "sistem": "talno_mokri",
                    "izolacija": "100%",
    
                    "hidravlicnoUravnotezenje": "staticnoDviznihVodov",
                    "regulacijaTemperature": "referencniProstor",
    
                    "mocRegulatorja": 1,
                    "steviloRegulatorjev": 1
                }
            ]
        }
        EOT;

        $sistem = new ToplovodniOgrevalniSistem($config);

        $sistem->analiza($cona, $okolje);

        $izgubePrenosnikov = $sistem->koncniPrenosniki[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubePrenosnikov);
        $expected = [96.59, 68.03, 48.25, 25.03, 7.28, 0.00, 0.00, 0.00, 8.37, 36.03, 82.22, 101.38];
        $this->assertEquals($expected, $roundedResult);

        $izgubeRazvoda = $sistem->razvodi[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [226.53, 153.64, 100.05, 47.66, 8.18, 0.0, 0.0, 0.0, 8.1, 62.17, 159.92, 227.23];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [27.86, 24.47, 26.17, 20.47, 3.25, 0.00, 0.00, 0.00, 3.12, 25.70, 26.15, 27.87];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaEnergija = $sistem->generatorji[0]->potrebnaEnergija['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaEnergija);
        $expected = [528.33, 332.86, 178.68, 68.20, 11.31, 0.00, 0.00, 0.00, 11.43, 89.09, 315.28, 504.96];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->generatorji[0]->potrebnaElektricnaEnergija['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [2.48, 1.56, 0.82, 0.31, 0.05, 0.00, 0.00, 0.00, 0.05, 0.40, 1.47, 2.37];
        $this->assertEquals($expected, $roundedResult); 

        $potrebnaToplotaZaGenerator = $sistem->ogrevanje->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [1419.58, 920.31, 539.49, 221.52, 39.05, 0.00, 0.00, 0.00, 39.09, 292.97, 953.22, 1424.66];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergijaSistema = $sistem->ogrevanje->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergijaSistema);
        $expected = [31.09, 26.70, 27.74, 21.37, 3.40, 0.00, 0.00, 0.00, 3.26, 26.85, 28.34, 30.99];
        $this->assertEquals($expected, $roundedResult);

        $obnovljivaEnergija = $sistem->ogrevanje->obnovljivaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $obnovljivaEnergija);
        $expected = [891.25, 587.45, 360.81, 153.33, 27.74, 0.00, 0.00, 0.00, 27.67, 203.88, 637.95, 919.71];
        $this->assertEquals($expected, $roundedResult);
    }
}