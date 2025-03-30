<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\OHTSistemi;

use App\Calc\GF\TSS\OHTSistemi\ToplovodniOHTSistem;
use PHPUnit\Framework\TestCase;

final class ToplovodniOHTSistemOgrevanjeTCTest extends TestCase
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

        $sistem = new ToplovodniOHTSistem($config);

        $sistem->analiza($cona, $okolje);

        $izgubePrenosnikov = $sistem->koncniPrenosniki[0]->toplotneIzgube['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubePrenosnikov);
        $expected = [93.78, 65.54, 45.73, 23.31, 6.59, 0.00, 0.00, 0.00, 7.47, 33.26, 78.89, 98.38];
        $this->assertEquals($expected, $roundedResult);

        $izgubeRazvoda = $sistem->razvodi[0]->toplotneIzgube['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [220.90, 148.98, 96.00, 44.38, 7.40, 0.00, 0.00, 0.00, 7.23, 58.14, 154.55, 221.50];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[0]->potrebnaElektricnaEnergija['ogrevanje'];
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
        //$this->assertEquals($expected, $roundedResult);

        $potrebnaToplotaZaGenerator = $sistem->ogrevanje->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [1379.15, 887.67, 512.54, 206.28, 35.34, 0.00, 0.00, 0.00, 34.89, 271.18, 915.74, 1383.44];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergijaSistema = $sistem->ogrevanje->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergijaSistema);
        $expected = [30.94, 26.59, 27.65, 19.9, 3.07, 0.00, 0.00, 0.00, 2.91, 25.82, 28.21, 30.84];
        //$this->assertEquals($expected, $roundedResult);

        $obnovljivaEnergija = $sistem->ogrevanje->obnovljivaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $obnovljivaEnergija);
        $expected = [865.87, 566.62, 342.78, 142.78, 25.11, 0.00, 0.00, 0.00, 24.69, 188.72, 612.86, 893.09];
        $this->assertEquals($expected, $roundedResult);
    }

    public function testTCVodaVoda(): void
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
                    "vrsta": "TC_vodavoda",
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

        $sistem = new ToplovodniOHTSistem($config);

        $sistem->analiza($cona, $okolje);

        $izgubePrenosnikov = $sistem->koncniPrenosniki[0]->toplotneIzgube['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubePrenosnikov);
        $expected = [93.78, 65.54, 45.73, 23.31, 6.59, 0.00, 0.00, 0.00, 7.47, 33.26, 78.89, 98.38];
        $this->assertEquals($expected, $roundedResult);

        $izgubeRazvoda = $sistem->razvodi[0]->toplotneIzgube['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [220.90, 148.98, 96.00, 44.38, 7.40, 0.00, 0.00, 0.00, 7.23, 58.14, 154.55, 221.50];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[0]->potrebnaElektricnaEnergija['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [27.79, 24.41, 26.12, 19.06, 2.94, 0.00, 0.00, 0.00, 2.79, 24.73, 26.08, 27.79];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaEnergija = $sistem->generatorji[0]->E_tc['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaEnergija);
        $expected = [444.07, 285.82, 165.03, 66.42, 11.38, 0.00, 0.00, 0.00, 11.23, 87.32, 294.86, 445.45];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->generatorji[0]->potrebnaElektricnaEnergija['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [1.97, 1.27, 0.73, 0.29, 0.05, 0.00, 0.00, 0.00, 0.05, 0.39, 1.31, 1.98];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergijaSistema = $sistem->ogrevanje->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergijaSistema);
        $expected = [30.50, 26.35, 27.59, 19.91, 3.08, 0.00, 0.00, 0.00, 2.92, 25.84, 28.11, 30.51];
        $this->assertEquals($expected, $roundedResult);

        $obnovljivaEnergija = $sistem->ogrevanje->obnovljivaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $obnovljivaEnergija);
        $expected = [935.08, 601.85, 347.51, 139.86, 23.96, 0.00, 0.00, 0.00, 23.66, 183.86, 620.89, 937.99];
        $this->assertEquals($expected, $roundedResult);
    }
}
