<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\OHTSistemi;

use App\Calc\GF\TSS\OHTSistemi\ToplovodniOHTSistem;
use PHPUnit\Framework\TestCase;

final class ToplovodniOHTSistemBiomasaTest extends TestCase
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
            "id": "TC",
            "idCone": "Cona1",
            "vrsta": "toplovodni",
            "energent": "biomasa",
            
            "ogrevanje": {
                "rezim": "40/30",
                "generatorji": ["KOTEL"],
                "razvodi": ["Ogrevanje"],
                "prenosniki": ["Talno"]
            },

            "generatorji": [
                {
                    "id": "KOTEL",
                    "vrsta": "biomasa",
                    "nazivnaMoc": 12,
                    "tip": "standardniZAvtomatskimDodajanjemGoriva",
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

        // betah
        $beta_h_gen = $data->porociloNizi['betaH']->vrednosti;
        $roundedResult = array_map(fn($el) => round($el, 3), $beta_h_gen);
        $expected = [0.144, 0.101, 0.051, 0.031, 0.035, 0.000, 0.000, 0.000, 0.036, 0.032, 0.096, 0.144];
        $this->assertEquals($expected, $roundedResult);

        $izgubePrenosnikov = $sistem->koncniPrenosniki[0]->toplotneIzgube['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubePrenosnikov);
        $expected = [87.09, 59.99, 40.56, 20.87, 6.34, 0.00, 0.00, 0.00, 7.16, 28.66, 71.46, 91.23];
        $this->assertEquals($expected, $roundedResult);

        $izgubeRazvoda = $sistem->razvodi[0]->toplotneIzgube['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [207.48, 138.47, 87.58, 39.74, 7.13, 0.00, 0.00, 0.00, 6.93, 50.10, 142.48, 207.79];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[0]->potrebnaElektricnaEnergija['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [27.60, 24.27, 26.01, 17.07, 2.83, 0.00, 0.00, 0.00, 2.67, 21.31, 25.92, 27.60];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaEnergija = $sistem->generatorji[0]->toplotneIzgube['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaEnergija);
        $expected = [346.01, 250.87, 198.15, 110.40, 18.91, 0.00, 0.00, 0.00, 18.04, 138.37, 261.51, 346.40];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->generatorji[0]->potrebnaElektricnaEnergija['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [32.92, 23.89, 18.91, 13.93, 11.74, 10.80, 11.16, 11.16, 11.37, 15.12, 24.91, 32.96];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaToplotaZaGenerator = $sistem->ogrevanje->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [1508.49, 986.49, 606.66, 273.23, 49.02, 0.00, 0.00, 0.00, 47.66, 344.47, 1012.53, 1510.88];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergijaSistema = $sistem->ogrevanje->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergijaSistema);
        $expected = [61.26, 48.83, 45.67, 31.49, 14.65, 10.80, 11.16, 11.16, 14.11, 37.05, 51.55, 61.31];
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
            "energent": "biomasa",
            
            "tsv": {
                "rezim": "55/45",
                "generatorji": ["KOTEL"],
                "razvodi": ["TSV"],
                "hranilniki": ["TSV"]
            },

            "generatorji": [
                {
                    "id": "KOTEL",
                    "vrsta": "biomasa",
                    "nazivnaMoc": 12,
                    "tip": "standardniZAvtomatskimDodajanjemGoriva",
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

        $izgubeRazvoda = $sistem->razvodi[0]->toplotneIzgube['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [179.49, 162.12, 179.49, 173.70, 179.49, 173.70, 179.49, 179.49, 173.70, 179.49, 173.70, 179.49];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[0]->potrebnaElektricnaEnergija['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);

        $izgubeHranilnika = $sistem->hranilniki[0]->toplotneIzgube['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeHranilnika);
        $expected = [54.67, 49.38, 54.67, 52.90, 54.67, 52.90, 54.67, 54.67, 52.90, 54.67, 52.90, 54.67];
        $this->assertEquals($expected, $roundedResult);

        $toplotneIzgube = $sistem->generatorji[0]->toplotneIzgube['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $toplotneIzgube);
        $expected = [88.21, 79.67, 88.21, 85.36, 88.21, 85.36, 88.21, 88.21, 85.36, 88.21, 85.36, 88.21];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaToplotaZaGenerator = $sistem->generatorji[0]->vneseneIzgube['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [347.95, 314.28, 347.95, 336.72, 347.95, 336.72, 347.95, 347.95, 336.72, 347.95, 336.72, 347.95];
        $this->assertEquals($expected, $roundedResult);

        /*$potrebnaElektricnaEnergija = $sistem->generatorji[0]->potrebnaElektricnaEnergija['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [1.83, 1.65, 1.83, 1.77, 1.83, 1.77, 1.83, 1.83, 1.77, 1.83, 1.77, 1.83];
        $this->assertEquals($expected, $roundedResult);*/

        $potrebnaToplotaZaGenerator = $sistem->tsv->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [403.85, 364.76, 403.85, 390.82, 403.85, 390.82, 403.85, 403.85, 390.82, 403.85, 390.82, 403.85];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergijaSistema = $sistem->tsv->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergijaSistema);
        $expected = [15.20, 13.73, 15.20, 14.71, 15.20, 14.71, 15.20, 15.20, 14.71, 15.20, 14.71, 15.20];
        $this->assertEquals($expected, $roundedResult);

        $vracljiveIzgubeVOgrevanje = $sistem->vracljiveIzgubeVOgrevanje;
        $roundedResult = array_map(fn($el) => round($el, 2), $vracljiveIzgubeVOgrevanje);
        $expected = [459.67, 415.19, 459.67, 444.84, 459.67, 444.84, 459.67, 459.67, 444.84, 459.67, 444.84, 459.67];
        $this->assertEquals($expected, $roundedResult);
    }
}
