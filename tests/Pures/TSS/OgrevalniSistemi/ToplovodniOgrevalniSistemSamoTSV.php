<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Calc\GF\TSS\OgrevalniSistemi\ToplovodniOgrevalniSistem;

final class ToplovodniOgrevalniSistemSamoTSV extends TestCase
{
    public function testToplotneIzgube(): void
    {
        $cona = new \stdClass();
        $cona->id = "Cona1";
        $cona->notranjaTOgrevanje = 20;
        $cona->energijaTSV = [114.253972, 103.197136, 114.253972, 110.56836, 114.253972, 110.56836,114.253972, 114.253972, 110.56836, 114.253972, 110.56836, 114.25392];
        $cona->energijaOgrevanje = [1206.6498424364968, 746.3227400063487, 390.0830744323009, 135.71842830225137, 19.217650278634665, 0, 0, 0, 17.900038879386443, 179.47284892375217, 761.5928197350089, 1208.7268259786833];
        $cona->specVentilacijskeIzgube = 8.184;
        $cona->specTransmisijskeIzgube = 143.8765034039049;
        $cona->sirina = 8;
        $cona->dolzina = 10;
        $cona->steviloEtaz = 3;
        $cona->etaznaVisina = 3;

        $okolje = new \stdClass();
        $okolje->projektnaZunanjaT = -13;

        $config = <<<EOT
        {
            "id": "TC",
            "idCone": "Cona1",
            "vrsta": "toplovodni",
            "energent": "elektrika",
            
            "tsv": {
                "rezim": "55/45",
                "generatorji": ["TC"],
                "razvodi": ["TSV"],
                "hranilniki": ["TSV"]
            },
    
            "generatorji": [
                {
                    "id": "TC",
                    "vrsta": "TC_zrakvodaSTV",
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

        $sistem = new ToplovodniOgrevalniSistem($config);

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

        $potrebnaEnergija = $sistem->generatorji[0]->potrebnaEnergija;
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
}