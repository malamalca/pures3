<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Calc\GF\TSS\OgrevalniSistemi\ToplovodniOgrevalniSistem;

final class ToplovodniOgrevalniSistemOgrevanjeTCTest extends TestCase
{
    public function testTCZrakVoda(): void
    {
        /** @var array $coneIn */
        $coneIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'cone.json'));
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
        $expected = [93.78, 65.54, 45.73, 23.31, 6.59, 0.00, 0.00, 0.00, 7.47, 33.26, 78.89, 98.38];
        $this->assertEquals($expected, $roundedResult);

        $izgubeRazvoda = $sistem->razvodi[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [220.90, 148.98, 96.00, 44.38, 7.40, 0.00, 0.00, 0.00, 7.23, 58.14, 154.55, 221.50];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [27.79, 24.41, 26.12, 19.06, 2.94, 0.00, 0.00, 0.00, 2.79, 24.73, 26.08, 27.79];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaEnergija = $sistem->generatorji[0]->E_tc['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaEnergija);
        $expected = [513.28, 321.05, 169.76, 63.50, 10.23, 0.00, 0.00, 0.00, 10.20, 82.46, 302.88, 490.35];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->generatorji[0]->potrebnaElektricnaEnergija['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [2.41, 1.51, 0.78, 0.29, 0.04, 0.00, 0.00, 0.00, 0.04, 0.37, 1.41, 2.30];
        $this->assertEquals($expected, $roundedResult); 

        $potrebnaToplotaZaGenerator = $sistem->ogrevanje->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [1379.15, 887.67, 512.54, 206.28, 35.34, 0.00, 0.00, 0.00, 34.89, 271.18, 915.74, 1383.44];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergijaSistema = $sistem->ogrevanje->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergijaSistema);
        $expected = [30.94, 26.59, 27.65, 19.9, 3.07, 0.00, 0.00, 0.00, 2.91, 25.82, 28.21, 30.84];
        $this->assertEquals($expected, $roundedResult);

        $obnovljivaEnergija = $sistem->ogrevanje->obnovljivaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $obnovljivaEnergija);
        $expected = [865.87, 566.62, 342.78, 142.78, 25.11, 0.00, 0.00, 0.00, 24.69, 188.72, 612.86, 893.09];
        $this->assertEquals($expected, $roundedResult);
    }

    /*public function testTCZemljaVoda(): void
    {
        $coneIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'cone.json'));
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
                    "vrsta": "TC_zemljavoda",
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
        $expected = [326.49, 211.66, 124.08, 50.95, 8.98, 0.00, 0.00, 0.00, 8.99, 67.38, 219.23, 327.66];
        $this->assertEquals($expected, $roundedResult);

        /*$potrebnaElektricnaEnergija = $sistem->generatorji[0]->potrebnaElektricnaEnergija['ogrevanje'];
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
    }*/
}