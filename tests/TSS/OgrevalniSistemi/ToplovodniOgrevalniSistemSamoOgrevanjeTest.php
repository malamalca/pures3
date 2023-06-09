<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Calc\TSS\OgrevalniSistemi\ToplovodniOgrevalniSistem;

final class ToplovodniOgrevalniSistemSamoOgrevanjeTest extends TestCase
{
    public function testToplotneIzgube(): void
    {
        $cona = new \stdClass();
        $cona->id = "Cona1";
        $cona->notranjaTOgrevanje = 20;
        $cona->zunanjaT = -13;
        $cona->potrebaTSV = [114.253972, 103.197136, 114.253972, 110.56836, 114.253972, 110.56836,114.253972, 114.253972, 110.56836, 114.253972, 110.56836, 114.25392];
        $cona->energijaOgrevanje = [1206.7067763529, 746.3679541588, 390.1171250327, 135.7338005150, 19.2204599611, 0, 0, 0, 17.9030263837, 179.4962090259, 761.6441705740, 1208.7845887780];
        $cona->specVentilacijskeIzgube = 8.184;
        $cona->specTransmisijskeIzgube = 143.8765034039049;
        $cona->sirina = 8;
        $cona->dolzina = 10;
        $cona->steviloEtaz = 3;
        $cona->etaznaVisina = 3;

        $okolje = new \stdClass();
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
                    "nazivnaMoc": 6,
                    "nazivniCOP": 3,
                    "elektricnaMocNaPrimarnemKrogu": 6,
                    "elektricnaMocNaSekundarnemKrogu": 6
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
        $expected = [109.18, 74.64, 49.41, 23.44, 6.09, 0.00, 0.00, 0.00, 6.80, 34.10, 90.45, 114.83];
        $this->assertEquals($expected, $roundedResult);

        $izgubeRazvoda = $sistem->razvodi[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [246.28, 162.67, 100.07, 43.58, 6.69, 0.00, 0.00, 0.00, 6.44, 58.22, 169.72, 247.51];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [28.14, 24.59, 26.17, 18.67, 2.65, 0.00, 0.00, 0.00, 2.47, 24.69, 26.29, 28.16];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaEnergija = $sistem->generatorji[0]->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaEnergija);
        $expected = [614.11, 346.66, 188.46, 63.69, 9.50, 0.00, 0.00, 0.00, 9.17, 84.92, 355.36, 606.75];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->generatorji[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [3.85, 2.17, 1.17, 0.40, 0.06, 0.00, 0.00, 0.00, 0.06, 0.53, 2.22, 3.80];
        $this->assertEquals($expected, $roundedResult); 

        $potrebnaToplotaZaGenerator = $sistem->ogrevanje->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [1562.16, 983.67, 539.60, 202.76, 32.00, 0.00, 0.00, 0.00, 31.15, 271.82, 1021.81, 1571.13];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergijaSistema = $sistem->ogrevanje->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergijaSistema);
        $expected = [32.73, 27.43, 28.09, 19.61, 2.79, 0.00, 0.00, 0.00, 2.60, 25.94, 29.22, 32.70];
        $this->assertEquals($expected, $roundedResult);

        $obnovljivaEnergija = $sistem->ogrevanje->obnovljivaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $obnovljivaEnergija);
        $expected = [948.05, 637.02, 351.14, 139.07, 22.50, 0.00, 0.00, 0.00, 21.98, 186.90, 666.45, 964.38];
        $this->assertEquals($expected, $roundedResult);
    }
}