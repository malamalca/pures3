<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Calc\GF\TSS\OgrevalniSistemi\ToplovodniOgrevalniSistem;

final class ToplovodniOgrevalniSistemPlinskiKotelTest extends TestCase
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
            "energent": "zemeljskiPlin",
            
            "ogrevanje": {
                "rezim": "40/30",
                "generatorji": ["KOTEL"],
                "razvodi": ["Ogrevanje"],
                "prenosniki": ["Talno"]
            },
    
            "generatorji": [
                {
                    "id": "KOTEL",
                    "vrsta": "plinskiKotel",
                    "tip": "kondenzacijski",
                    "nazivnaMoc": 12,
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

        $sistem = new ToplovodniOgrevalniSistem($config);

        $sistem->analiza($cona, $okolje);

        $izgubePrenosnikov = $sistem->koncniPrenosniki[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubePrenosnikov);
        $expected = [91.00, 63.32, 43.75, 22.38, 6.53, 0.00, 0.00, 0.00, 7.40, 31.43, 75.93, 95.41];
        $this->assertEquals($expected, $roundedResult);

        $izgubeRazvoda = $sistem->razvodi[0]->toplotneIzgube;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [215.34, 144.78, 92.79, 42.60, 7.34, 0.00, 0.00, 0.00, 7.16, 54.94, 149.75, 215.82];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->razvodi[0]->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [27.71, 24.35, 26.08, 18.30, 2.92, 0.00, 0.00, 0.00, 2.76, 23.37, 26.02, 27.72];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaEnergija = $sistem->generatorji[0]->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaEnergija['ogrevanje']);
        $expected = [110.76, 82.38, 68.12, 40.90, 6.69, 0.00, 0.00, 0.00, 6.39, 52.39, 86.38, 110.93];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergija = $sistem->generatorji[0]->potrebnaElektricnaEnergija['ogrevanje'];
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergija);
        $expected = [18.39, 11.79, 6.75, 2.72, 0.48, 0.00, 0.00, 0.00, 0.47, 3.52, 12.12, 18.44];
        $this->assertEquals($expected, $roundedResult); 

        $potrebnaToplotaZaGenerator = $sistem->ogrevanje->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaToplotaZaGenerator);
        $expected = [1306.38, 847.56, 503.99, 215.24, 37.59, 0.00, 0.00, 0.00, 36.88, 278.04, 872.75, 1309.61];
        $this->assertEquals($expected, $roundedResult);

        $potrebnaElektricnaEnergijaSistema = $sistem->ogrevanje->potrebnaElektricnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektricnaEnergijaSistema);
        $expected = [46.85, 36.81, 33.57, 21.55, 3.48, 0.00, 0.00, 0.00, 3.31, 27.56, 38.86, 46.90];
        $this->assertEquals($expected, $roundedResult);

        /*$obnovljivaEnergija = $sistem->ogrevanje->obnovljivaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $obnovljivaEnergija);
        $expected = [865.87, 566.62, 342.78, 142.78, 25.11, 0.00, 0.00, 0.00, 24.69, 188.72, 612.86, 893.09];
        $this->assertEquals($expected, $roundedResult);*/
    }
}