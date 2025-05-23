<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\Razvodi;

use App\Calc\GF\TSS\OHTSistemi\Izbire\VrstaRezima;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\PloskovnoOgrevalo;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\DvocevniRazvod;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire\VrstaRazvodnihCevi;
use App\Calc\GF\TSS\OHTSistemi\Sistemi\Ogrevanje;
use App\Calc\GF\TSS\OHTSistemi\ToplovodniOHTSistem;
use PHPUnit\Framework\TestCase;

final class DvocevniRazvodTest extends TestCase
{
    public function testDolzinaCevi(): void
    {
        $cona = new \stdClass();
        $cona->dolzina = 10;
        $cona->sirina = 8;
        $cona->steviloEtaz = 3;
        $cona->etaznaVisina = 3;

        $config = <<<EOT
        {
            "vrsta": "dvocevni",
            "idPrenosnika": "TALNO",
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
        EOT;

        $razvod = new DvocevniRazvod($config);

        $dolzina = $razvod->dolzinaCevi(VrstaRazvodnihCevi::HorizontalniRazvod, $cona);
        $this->assertEquals(28.6, $dolzina);

        $dolzina = $razvod->dolzinaCevi(VrstaRazvodnihCevi::DvizniVod, $cona);
        $this->assertEquals(18, $dolzina);

        $dolzina = $razvod->dolzinaCevi(VrstaRazvodnihCevi::PrikljucniVod, $cona);
        $this->assertEquals(132, $dolzina);
    }

    public function testHidravlicnaMocCrpalke(): void
    {
        $configSistem = <<<EOT
        {
            "vrsta": "toplovodni",
            "energent": "elektrika",
            "rezim": "40/30"
        }
        EOT;
        $sistem = new ToplovodniOHTSistem($configSistem);
        //$sistem->standardnaMoc = 5.0179966123288615;

        $configPrenosnika = <<<EOT
        {
            "id": "TALNO",
            "vrsta": "ploskovnaOgrevala",

            "sistem": "talno_mokri",
            "izolacija": "100%",

            "hidravlicnoUravnotezenje": "staticnoDviznihVodov",
            "regulacijaTemperature": "referencniProstor",

            "mocRegulatorja": 1,
            "steviloRegulatorjev": 1
        }
        EOT;
        $prenosnik = new PloskovnoOgrevalo(json_decode($configPrenosnika));

        /** @var array $coneIn */
        $coneIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'cone.json'));
        $cona = $coneIn[0];

        $okolje = new \stdClass();
        $okolje->projektnaZunanjaT = -13;

        $config = <<<EOT
        {
            "vrsta": "dvocevni",
            "idPrenosnika": "TALNO",
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
        EOT;
        $razvod = new DvocevniRazvod(json_decode($config));

        $rezim = VrstaRezima::from('40/30');

        $hidravlicnaMoc = $razvod->izracunHidravlicneMoci($prenosnik, $rezim, $sistem, $cona, $okolje);

        $this->assertEquals(6.737, round($hidravlicnaMoc, 3));
    }

    public function testFaktorRabeEnergijeCrpalke(): void
    {
        $sistem = new \stdClass();

        $cona = new \stdClass();
        $cona->dolzina = 10;
        $cona->sirina = 8;
        $cona->steviloEtaz = 3;
        $cona->etaznaVisina = 3;
        $cona->notranjaTOgrevanje = 20;

        $config = <<<EOT
        {
            "vrsta": "dvocevni",
            "idPrenosnika": "TALNO",
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
        EOT;

        $razvod = new DvocevniRazvod(json_decode($config));

        $hidravlicnaMoc = 6.737259993559786;
        $faktor_fe = $razvod->izracunFaktorjaRabeEnergijeCrpalke($hidravlicnaMoc);

        $this->assertEquals(6.698, round($faktor_fe, 3));
    }

    public function testToplotneIzgube(): void
    {
        $cona = new \stdClass();
        $cona->dolzina = 10;
        $cona->sirina = 8;
        $cona->steviloEtaz = 3;
        $cona->etaznaVisina = 3;
        $cona->notranjaTOgrevanje = 20;
        $cona->energijaOgrevanje = [1206.707, 746.368, 390.117, 135.734, 19.220, 0.000, 0.000, 0.000, 17.903, 179.496, 761.644, 1208.785];
        $cona->specTransmisijskeIzgube = 143.8765034039049;
        $cona->specVentilacijskeIzgube = 8.184;

        $okolje = new \stdClass();
        $okolje->projektnaZunanjaT = -13;

        $configSistem = <<<EOT
        {
            "vrsta": "toplovodni",
            "energent": "elektrika"
        }
        EOT;
        $sistem = new ToplovodniOHTSistem($configSistem);

        $configPrenosnika = <<<EOT
        {
            "id": "TALNO",
            "vrsta": "ploskovnaOgrevala",

            "sistem": "talno_mokri",
            "izolacija": "100%",

            "hidravlicnoUravnotezenje": "staticnoDviznihVodov",
            "regulacijaTemperature": "referencniProstor",

            "mocRegulatorja": 1,
            "steviloRegulatorjev": 1
        }
        EOT;
        $prenosnik = new PloskovnoOgrevalo(json_decode($configPrenosnika));

        $config = <<<EOT
        {
            "vrsta": "dvocevni",
            "idPrenosnika": "TALNO",
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
        EOT;
        $razvod = new DvocevniRazvod(json_decode($config));

        $rezim = VrstaRezima::from('40/30');

        $sistem->koncniPrenosniki = [$prenosnik];
        $sistem->ogrevanje = new Ogrevanje();
        $sistem->ogrevanje->rezim = $rezim;

        $preneseneIzgube = [1315.89, 821.00, 439.53, 159.18, 25.31, 0.00, 0.00, 0.00, 24.71, 213.60, 852.09, 1323.62];

        $izgube = $razvod->toplotneIzgube($preneseneIzgube, $sistem, $cona, $okolje, ['namen' => 'ogrevanje']);
        $roundedResult = array_map(fn($el) => round($el, 2), $izgube['ogrevanje']);

        $expected = [246.28, 162.67, 100.07, 43.58, 6.69, 0.00, 0.00, 0.00, 6.44, 58.22, 169.72, 247.51];

        $this->assertEquals($expected, $roundedResult);
    }

    public function testPotrebnaElektricnaEnergija(): void
    {
        $cona = new \stdClass();
        $cona->dolzina = 10;
        $cona->sirina = 8;
        $cona->steviloEtaz = 3;
        $cona->etaznaVisina = 3;
        $cona->notranjaTOgrevanje = 20;
        $cona->energijaOgrevanje = [1206.707, 746.368, 390.117, 135.734, 19.220, 0.000, 0.000, 0.000, 17.903, 179.496, 761.644, 1208.785];
        $cona->specTransmisijskeIzgube = 143.8765034039049;
        $cona->specVentilacijskeIzgube = 8.184;

        $okolje = new \stdClass();
        $okolje->projektnaZunanjaT = -13;

        $configSistem = <<<EOT
        {
            "vrsta": "toplovodni",
            "energent": "elektrika",
            "rezim": "40/30"
        }
        EOT;
        $sistem = new ToplovodniOHTSistem($configSistem);

        $configPrenosnika = <<<EOT
        {
            "id": "TALNO",
            "vrsta": "ploskovnaOgrevala",

            "sistem": "talno_mokri",
            "izolacija": "100%",

            "hidravlicnoUravnotezenje": "staticnoDviznihVodov",
            "regulacijaTemperature": "referencniProstor",

            "mocRegulatorja": 1,
            "steviloRegulatorjev": 1
        }
        EOT;
        $prenosnik = new PloskovnoOgrevalo(json_decode($configPrenosnika));

        $config = <<<EOT
        {
            "vrsta": "dvocevni",
            "idPrenosnika": "TALNO",
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
        EOT;
        $razvod = new DvocevniRazvod(json_decode($config));

        $rezim = VrstaRezima::from('40/30');

        $sistem->koncniPrenosniki = [$prenosnik];
        $sistem->ogrevanje = new Ogrevanje();
        $sistem->ogrevanje->rezim = $rezim;

        $preneseneIzgube = [1315.89, 821.00, 439.53, 159.18, 25.31, 0.00, 0.00, 0.00, 24.71, 213.60, 852.09, 1323.62];

        $potrebnaElektrika = $razvod->potrebnaElektricnaEnergija($preneseneIzgube, $sistem, $cona, $okolje, ['namen' => 'ogrevanje']);

        $roundedResult = array_map(fn($el) => round($el, 2), $potrebnaElektrika['ogrevanje']);

        $expected = [28.14, 24.59, 26.17, 18.67, 2.65, 0.00, 0.00, 0.00, 2.47, 24.69, 26.29, 28.16];

        $this->assertEquals($expected, $roundedResult);
    }
}
