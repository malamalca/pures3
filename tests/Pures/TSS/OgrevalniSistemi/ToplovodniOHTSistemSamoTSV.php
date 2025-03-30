<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\OHTSistemi;

use App\Calc\GF\TSS\OHTSistemi\ToplovodniOHTSistem;
use PHPUnit\Framework\TestCase;

final class ToplovodniOHTSistemSamoTSV extends TestCase
{
    public function testElektricniGrelec(): void
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
            
            "tsv": {
                "steviloIteracij": 1,
                "rezim": "55/45",
                "generatorji": ["ElGrelec"],
                "razvodi": ["TSV"],
                "hranilniki": ["TSV"]
            },
    
            "generatorji": [
                {
                    "id": "ElGrelec",
                    "vrsta": "elektricniGrelnik",
                    "nazivnaMoc": 6
                }
            ],
    
            "razvodi": [
                {
                    "vrsta": "toplavoda",
                    "id": "TSV",
                    "crpalka": {},
                    "ceviHorizontaliVodi": {
                    },
                    "ceviDvizniVodi": {
                    },
                    "ceviPrikljucniVodi": {
                    }
                }
            ],

            "hranilniki": [
                {
                    "id": "TSV",
                    "vrsta": "neposrednoOgrevan",
                    "volumen": 250,
                    "istiProstorKotGrelnik": true,
                    "znotrajOvoja": true
                }
            ]
        }
        EOT;

        $sistem = new ToplovodniOHTSistem($config);

        $sistem->analiza($cona, $okolje);

        $izgubeRazvoda = $sistem->razvodi[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [457.26, 413.00, 457.26, 442.51, 457.26, 442.51, 457.26, 457.26, 442.51, 457.26, 442.51, 457.26];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [6.99, 6.31, 6.99, 6.76, 6.99, 6.76, 6.99, 6.99, 6.76, 6.99, 6.76, 6.99];
        $this->assertEquals($expected, $roundedResult);

        $vrnjenaElektricnaEnergijaAux = $sistem->razvodi[0]->vracljiveIzgubeAux;
        $roundedResult = array_map(fn($el) => round($el, 2), $vrnjenaElektricnaEnergijaAux);
        $expected = [1.75, 1.58, 1.75, 1.69, 1.75, 1.69, 1.75, 1.75, 1.69, 1.75, 1.69, 1.75];
        $this->assertEquals($expected, $roundedResult);

        $izgubeHranilnika = $sistem->hranilniki[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeHranilnika);
        $expected = [44.95, 40.60, 44.95, 43.50, 44.95, 43.50, 44.95, 44.95, 43.50, 44.95, 43.50, 44.95];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaToplotaZaGenerator = $sistem->tsv->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [614.71, 555.23, 614.71, 594.88, 614.71, 594.88, 614.71, 614.71, 594.88, 614.71, 594.88, 614.71];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergijaSistema = $sistem->tsv->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergijaSistema);
        $expected = [6.99, 6.31, 6.99, 6.76, 6.99, 6.76, 6.99, 6.99, 6.76, 6.99, 6.76, 6.99];
        $this->assertEquals($expected, $roundedResult);

        $vracljiveIzgubeVOgrevanje = $sistem->tsv->vracljiveIzgubeVOgrevanje;
        $roundedResult = array_map(fn($el) => round($el, 2), $vracljiveIzgubeVOgrevanje);
        $expected = [503.95, 455.18, 503.95, 487.70, 503.95, 487.70, 503.95, 503.95, 487.70, 503.95, 487.70, 503.95];
        $this->assertEquals($expected, $roundedResult);
    }

    public function testToplotneIzgube(): void
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
            
            "tsv": {
                "steviloIteracij": 1,
                "rezim": "55/45",
                "generatorji": ["TC"],
                "razvodi": ["TSV"],
                "hranilniki": ["TSV"]
            },
    
            "generatorji": [
                {
                    "id": "TC",
                    "vrsta": "TC_zrakvodaTSV",
                    "nazivnaMoc": 6,
                    "nazivniCOP": 3,
                    "elektricnaMocNaPrimarnemKrogu": 6,
                    "elektricnaMocNaSekundarnemKrogu": 6
                }
            ],
    
            "razvodi": [
                {
                    "vrsta": "toplavoda",
                    "id": "TSV",
                    "crpalka": {},
                    "ceviHorizontaliVodi": {
                    },
                    "ceviDvizniVodi": {
                    },
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

        $izgubeRazvoda = $sistem->razvodi[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [457.26, 413.00, 457.26, 442.51, 457.26, 442.51, 457.26, 457.26, 442.51, 457.26, 442.51, 457.26];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [6.99, 6.31, 6.99, 6.76, 6.99, 6.76, 6.99, 6.99, 6.76, 6.99, 6.76, 6.99];
        $this->assertEquals($expected, $roundedResult);

        $izgubeHranilnika = $sistem->hranilniki[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeHranilnika);
        $expected = [54.67, 49.38, 54.67, 52.90, 54.67, 52.90, 54.67, 54.67, 52.90, 54.67, 52.90, 54.67];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaEnergija = $sistem->generatorji[0]->potrebnaElektricnaEnergija['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaEnergija);
        $expected = [331.35, 276.60, 299.86, 263.28, 246.00, 217.19, 216.30, 220.20, 231.14, 271.48, 291.78, 327.07];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->generatorji[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [1.62, 1.30, 1.40, 1.19, 1.11, 1.00, 1.01, 1.02, 1.05, 1.23, 1.36, 1.59];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaToplotaZaGenerator = $sistem->tsv->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [624.43, 564.00, 624.43, 604.28, 624.43, 604.28, 624.43, 624.43, 604.28, 624.43, 604.28, 624.43];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergijaSistema = $sistem->tsv->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergijaSistema);
        $expected = [8.61, 7.61, 8.39, 7.95, 8.10, 7.77, 8.00, 8.01, 7.81, 8.21, 8.13, 8.58];
        $this->assertEquals($expected, $roundedResult);

        $obnovljivaEnergija = $sistem->tsv->obnovljivaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $obnovljivaEnergija);
        $expected = [293.08, 287.40, 324.56, 341.00, 378.43, 387.09, 408.13, 404.23, 373.15, 352.95, 312.50, 297.35];
        $this->assertEquals($expected, $roundedResult);

        $vracljiveIzgubeVOgrevanje = $sistem->tsv->vracljiveIzgubeVOgrevanje;
        $roundedResult = array_map(fn($el) => round($el, 2), $vracljiveIzgubeVOgrevanje);
        $expected = [513.67, 463.96, 513.67, 497.10, 513.67, 497.10, 513.67, 513.67, 497.10, 513.67, 497.10, 513.67];
        $this->assertEquals($expected, $roundedResult);
    }

    public function testVecstanovajnskaReferencnaStavba(): void
    {
        /** @var array $coneIn */
        $coneIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'cone.json'));
        $cona = $coneIn[0];

        $okolje = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'okolje.json'));

        $config = <<<EOT
        {
            "id": "TC",
            "idCone": "Cona1",
            "vrsta": "toplovodni",
            "energent": "zemeljskiPlin",
            
            "tsv": {
                "steviloIteracij": 1,
                "rezim": "55/45",
                "generatorji": ["SSE", "PlinskiKotel"],
                "razvodi": ["TSV", "Solar"],
                "hranilniki": ["TSV"]
            },
    
            "generatorji": [
                {
                    "id":  "SSE",
                    "vrsta": "solarniPaneli",
                    "tip": "zastekljen",
                    "povrsina": 6.4,
                    "orientacija": "J",
                    "naklon": 30
                },
                {
                    "id": "PlinskiKotel",
                    "vrsta": "plinskiKotel",
                    "tip": "kondenzacijski",
                    "nazivnaMoc": 10,
                    "regulacija": "konstantnaTemperatura"
                }
            ],
    
            "razvodi": [
                {
                    "vrsta": "toplavoda",
                    "id": "TSV",
                    "ceviHorizontaliVodi": {
                    },
                    "ceviDvizniVodi": {
                    },
                    "ceviPrikljucniVodi": {
                    }
                },
                {
                    "vrsta": "solar",
                    "id": "Solar",
                    "idGeneratorja": "SSE"
                }
            ],

            "hranilniki": [
                {
                    "id": "TSV",
                    "vrsta": "solarniPosrednoOgrevan",
                    "volumen": 320,
                    "istiProstorKotGrelnik": true,
                    "znotrajOvoja": true
                }
            ]
        }
        EOT;

        $sistem = new ToplovodniOHTSistem($config);

        $sistem->analiza($cona, $okolje);

        // -RAZVOD TSV V OBJEKT-------------------------------------------------------------------------------------- //
        $izgubeRazvoda = $sistem->razvodi[0]->toplotneIzgube['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [179.49, 162.12, 179.49, 173.70, 179.49, 173.70, 179.49, 179.49, 173.70, 179.49, 173.70, 179.49];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[0]->potrebnaElektricnaEnergija['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);

        $vrnjenaElektricnaEnergijaAux = $sistem->razvodi[0]->vracljiveIzgubeAux['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $vrnjenaElektricnaEnergijaAux);
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);

        $vracljiveIzgubeTSV = $sistem->razvodi[0]->vracljiveIzgubeTSV['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $vracljiveIzgubeTSV);
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);

        // -RAZVOD SOLARNI KOLEKTORJI-------------------------------------------------------------------------------- //
        $izgubeRazvoda = $sistem->razvodi[1]->toplotneIzgube['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[1]->potrebnaElektricnaEnergija['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[1]->potrebnaElektricnaEnergija['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [2.70, 4.22, 6.40, 7.93, 9.30, 9.32, 10.26, 9.49, 7.09, 4.54, 2.39, 1.95];
        $this->assertEquals($expected, $roundedResult);

        $vracljiveIzgubeTSV = $sistem->razvodi[1]->vracljiveIzgubeTSV['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $vracljiveIzgubeTSV);
        $expected = [0.68, 1.05, 1.60, 1.98, 2.33, 2.33, 2.57, 2.37, 1.77, 1.13, 0.60, 0.49];
        $this->assertEquals($expected, $roundedResult);

        $vracljiveIzgubeTSVAux = $sistem->razvodi[1]->vracljiveIzgubeAux['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $vracljiveIzgubeTSVAux);
        $expected = [0.68, 1.05, 1.60, 1.98, 2.33, 2.33, 2.57, 2.37, 1.77, 1.13, 0.60, 0.49];
        $this->assertEquals($expected, $roundedResult);

        // ---------------------------------------------------------------------------------------------------------- //

        $izgubeHranilnika = $sistem->hranilniki[0]->toplotneIzgube['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeHranilnika);
        $expected = [85.18, 76.94, 85.18, 82.43, 85.18, 82.43, 85.18, 85.18, 82.43, 85.18, 82.43, 85.18];
        $this->assertEquals($expected, $roundedResult);

        $vracljiveIzgubeWW = $sistem->hranilniki[0]->vracljiveIzgube['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $vracljiveIzgubeWW);
        $expected = [85.18, 76.94, 85.18, 82.43, 85.18, 82.43, 85.18, 85.18, 82.43, 85.18, 82.43, 85.18];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->hranilniki[0]->potrebnaElektricnaEnergija['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);

        // ---------------------------------------------------------------------------------------------------------- //

        $nepokritaEnergija = $sistem->generatorji[0]->nepokritaEnergija['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $nepokritaEnergija);
        $expected = [368.89, 238.07, 179.62, 102.38, 58.11, 38.50, 17.31, 37.02, 112.57, 244.74, 357.21, 395.95];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaToplotaZaGenerator = $sistem->generatorji[1]->vneseneIzgube['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [368.89, 238.07, 179.62, 102.38, 58.11, 38.50, 17.31, 37.02, 112.57, 244.74, 357.21, 395.95];
        $this->assertEquals($expected, $roundedResult);

        $X = $sistem->generatorji[0]->X;
        $roundedResult = array_map(fn($el) => round($el, 2), $X);
        $expected = [7.10, 6.77, 6.11, 5.45, 4.62, 4.12, 3.79, 3.79, 4.45, 5.28, 6.27, 6.93]; // prva iteracija
        //$expected = [7.12, 6.80, 6.14, 5.48, 4.65, 4.15, 3.82, 3.82, 4.48, 5.30, 6.29, 6.95]; // druga iteracija
        $this->assertEquals($expected, $roundedResult);

        $Y = $sistem->generatorji[0]->Y;
        $roundedResult = array_map(fn($el) => round($el, 2), $Y);
        $expected = [0.48, 0.83, 1.13, 1.45, 1.65, 1.71, 1.82, 1.68, 1.30, 0.80, 0.44, 0.35]; // prva iteracija
        //$expected = [0.48, 0.83, 1.14, 1.46, 1.66, 1.72, 1.83, 1.69, 1.30, 0.81, 0.44, 0.35]; // druga iteracija
        $this->assertEquals($expected, $roundedResult);

        $f_sol = $sistem->generatorji[0]->f_sol;
        $roundedResult = array_map(fn($el) => round($el, 3), $f_sol);
        $expected = [0.068, 0.334, 0.546, 0.733, 0.853, 0.900, 0.956, 0.907, 0.706, 0.382, 0.068, 0.000]; // prva iteracija
        //$expected = [0.069, 0.336, 0.548, 0.735, 0.856, 0.902, 0.959, 0.909, 0.709, 0.383, 0.068, 0.000]; // druga iteracija
        $this->assertEquals($expected, $roundedResult);

        $vracljiveIzgubeTSV = $sistem->generatorji[0]->vracljiveIzgubeTSV['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $vracljiveIzgubeTSV);
        $expected = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00];
        $this->assertEquals($expected, $roundedResult);

        // --PLINSKI KOTEL ------------------------------------------------------------------------------------------ //
        $potrebnaToplotaZaGenerator = $sistem->generatorji[1]->vneseneIzgube['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [368.89, 238.07, 179.62, 102.38, 58.11, 38.50, 17.31, 37.02, 112.57, 244.74, 357.21, 395.95];
        $this->assertEquals($expected, $roundedResult);

        $vracljiveIzgubeTSV = $sistem->generatorji[1]->vracljiveIzgubeTSV['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $vracljiveIzgubeTSV);
        $expected = [0.64, 0.41, 0.31, 0.18, 0.10, 0.07, 0.03, 0.06, 0.20, 0.42, 0.62, 0.69];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->generatorji[1]->potrebnaElektricnaEnergija['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [0.64, 0.41, 0.31, 0.18, 0.10, 0.07, 0.03, 0.06, 0.20, 0.42, 0.62, 0.69];
        $this->assertEquals($expected, $roundedResult);

        /*$vracljiveIzgubeTSV = $sistem->hranilniki[0]->vracljiveIzgubeTSV['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $vracljiveIzgubeTSV);
        $expected = [1.31, 1.47, 1.91, 2.16, 2.42, 2.39, 2.59, 2.43, 1.97, 1.56, 1.21, 1.17];
        $this->assertEquals($expected, $roundedResult);


        /*$potrebnaToplotaZaGenerator = $sistem->generatorji[0]->vneseneIzgube['tsv'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [377.60, 340.78, 377.01, 364.54, 376.49, 364.30, 376.32, 376.48, 364.73, 377.36, 365.48, 377.74];
        $this->assertEquals($expected, $roundedResult);

        /*$potrebnaElektricnaEnergijaSistema = $sistem->tsv->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergijaSistema);
        $expected = [6.99, 6.31, 6.99, 6.76, 6.99, 6.76, 6.99, 6.99, 6.76, 6.99, 6.76, 6.99];
        $this->assertEquals($expected, $roundedResult);*/

        /*$vracljiveIzgubeVOgrevanje = $sistem->tsv->vracljiveIzgubeVOgrevanje;
        $roundedResult = array_map(fn($el) => round($el, 2), $vracljiveIzgubeVOgrevanje);
        $expected = [503.95, 455.18, 503.95, 487.70, 503.95, 487.70, 503.95, 503.95, 487.70, 503.95, 487.70, 503.95];
        $this->assertEquals($expected, $roundedResult);*/
    }
}
