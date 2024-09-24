<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\OgrevalniSistemi;

use App\Calc\GF\TSS\OgrevalniSistemi\ToplovodniOHTSistem;
use PHPUnit\Framework\TestCase;

final class ToplovodniOHTSistemVrocevodTest extends TestCase
{
    public function testOgrevanje(): void
    {
        /** @var array $coneIn */
        $coneIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'cone.json'));
        $cona = $coneIn[0];

        $okolje = new \stdClass();
        $okolje->projektnaZunanjaT = -13;
        $okolje->zunanjaT = [-1, 1, 5, 9, 14, 17, 19, 19, 15, 10, 4, 0];

        $config = <<<EOT
        {
            "id": "VV",
            "idCone": "Cona1",
            "vrsta": "toplovodni",
            "energent": "daljinsko",
            
            "ogrevanje": {
                "rezim": "40/30",
                "generatorji": ["KOTEL"],
                "razvodi": ["Ogrevanje"],
                "prenosniki": ["Talno"]
            },

            "generatorji": [
                {
                    "id": "KOTEL",
                    "vrsta": "toplotnaPodpostaja",
                    "nazivnaMoc": 12,
                    "tip": "vrocevod",
                    "razredIzolacije": "primarna3sekundarna4",
                    "regulacija": "konstantnaTemperatura"
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
        $data = $sistem->generatorji[0]->export();

        $izgubePrenosnikov = $sistem->koncniPrenosniki[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubePrenosnikov);
        $expected = [90.25, 62.44, 42.70, 21.83, 6.49, 0.00, 0.00, 0.00, 7.35, 30.40, 74.76, 94.61];
        $this->assertEquals($expected, $roundedResult);

        $izgubeRazvoda = $sistem->razvodi[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [213.83, 143.11, 91.08, 41.57, 7.30, 0.00, 0.00, 0.00, 7.12, 53.13, 147.84, 214.28];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [27.69, 24.33, 26.06, 17.85, 2.90, 0.00, 0.00, 0.00, 2.74, 22.60, 25.99, 27.69];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaEnergija = $sistem->generatorji[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaEnergija['ogrevanje']);
        $expected = [45.60, 41.18, 45.60, 31.71, 5.14, 0.00, 0.00, 0.00, 4.86, 40.13, 44.13, 45.60];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->generatorji[0]->potrebnaElektricnaEnergija['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [10.00, 10.00, 10.00, 7.19, 1.13, 0.00, 0.00, 0.00, 1.10, 8.80, 10.00, 10.00];
        $this->assertEquals($expected, $roundedResult);

        $roundedResult = array_map(fn($el) => round($el, 2), $sistem->ogrevanje->potrebnaEnergija);
        $expected = [1374.12, 887.97, 525.63, 224.91, 39.99, 0.00, 0.00, 0.00, 39.20, 287.96, 913.27, 1377.35];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergijaSistema = $sistem->ogrevanje->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergijaSistema);
        $expected = [38.43, 35.00, 36.80, 25.55, 4.11, 0.00, 0.00, 0.00, 3.92, 32.06, 36.71, 38.44];
        $this->assertEquals($expected, $roundedResult);
    }

    public function testTSV(): void
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
            "energent": "daljinsko",
            
            "tsv": {
                "rezim": "55/45",
                "generatorji": ["KOTEL"],
                "razvodi": ["TSV"],
                "hranilniki": ["TSV"]
            },

            "generatorji": [
                {
                    "id": "KOTEL",
                    "vrsta": "toplotnaPodpostaja",
                    "nazivnaMoc": 12,
                    "tip": "vrocevod",
                    "razredIzolacije": "primarna3sekundarna4",
                    "regulacija": "konstantnaTemperatura"
                }
            ],
    
            "razvodi": [
                {
                    "vrsta": "toplavoda",
                    "id": "TSV",
                    "ceviHorizontaliVodi": {},
                    "ceviDvizniVodi": {},
                    "ceviPrikljucniVodi": {
                    }
                }
            ],

            "hranilniki": [
                {
                    "id": "TSV",
                    "vrsta": "posrednoOgrevan",
                    "volumen": 250,
                    "istiProstorKotGrelnik": true,
                    "znotrajOvoja": true
                }
            ]
        }
        EOT;

        $sistem = new ToplovodniOHTSistem($config);
        $sistem->analiza($cona, $okolje);
        $data = $sistem->generatorji[0]->export();

        $izgubeRazvoda = $sistem->razvodi[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [179.49, 162.12, 179.49, 173.70, 179.49, 173.70, 179.49, 179.49, 173.70, 179.49, 173.70, 179.49];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);

        $izgubeHranilnika = $sistem->hranilniki[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeHranilnika);
        $expected = [54.67, 49.38, 54.67, 52.90, 54.67, 52.90, 54.67, 54.67, 52.90, 54.67, 52.90, 54.67];
        $this->assertEquals($expected, $roundedResult);

        $toplotneIzgube = $sistem->generatorji[0]->toplotneIzgube['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $toplotneIzgube);
        $expected = [47.64, 43.03, 47.64, 46.10, 47.64, 46.10, 47.64, 47.64, 46.10, 47.64, 46.10, 47.64];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaToplotaZaGenerator = $sistem->generatorji[0]->vneseneIzgube['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [347.95, 314.28, 347.95, 336.72, 347.95, 336.72, 347.95, 347.95, 336.72, 347.95, 336.72, 347.95];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->generatorji[0]->potrebnaElektricnaEnergija['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [11.83, 11.65, 11.83, 11.77, 11.83, 11.77, 11.83, 11.83, 11.77, 11.83, 11.77, 11.83];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaToplotaZaGenerator = $sistem->tsv->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [395.59, 357.30, 395.59, 382.83, 395.59, 382.83, 395.59, 395.59, 382.83, 395.59, 382.83, 395.59];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergijaSistema = $sistem->tsv->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergijaSistema);
        $expected = [11.83, 11.65, 11.83, 11.77, 11.83, 11.77, 11.83, 11.83, 11.77, 11.83, 11.77, 11.83];
        $this->assertEquals($expected, $roundedResult);

        $vracljiveIzgubeVOgrevanje = $sistem->vracljiveIzgubeVOgrevanje;
        $roundedResult = array_map(fn($el) => round($el, 2), $vracljiveIzgubeVOgrevanje);
        $expected = [244.61, 221.91, 244.61, 237.04, 244.61, 237.04, 244.61, 244.61, 237.04, 244.61, 237.04, 244.61];
        $this->assertEquals($expected, $roundedResult);
    }
}
