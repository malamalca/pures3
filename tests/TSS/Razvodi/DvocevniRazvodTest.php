<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Calc\TSS\Razvodi\Izbire\VrstaRazvodnihCevi;
use App\Calc\TSS\OgrevalniSistemi\Izbire\VrstaRezima;
use App\Calc\TSS\Razvodi\DvocevniRazvod;
use App\Calc\TSS\Energenti\Elektrika;
use App\Calc\TSS\KoncniPrenosniki\PloskovnoOgrevalo;
use App\Calc\TSS\OgrevalniSistemi\ToplovodniOgrevalniSistem;

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
        $sistem = new ToplovodniOgrevalniSistem($configSistem);
        $sistem->standardnaMoc = 5.0179966123288615;

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
        $prenosnik = new PloskovnoOgrevalo($configPrenosnika);

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
        $razvod = new DvocevniRazvod($config);

        $hidravlicnaMoc = $razvod->izracunHidravlicneMoci($prenosnik, $sistem, $cona);

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

        $razvod = new DvocevniRazvod($config);

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
        $cona->zunanjaT = -13;
        $cona->energijaOgrevanje = [1206.707, 746.368, 390.117, 135.734, 19.220, 0.000, 0.000, 0.000, 17.903, 179.496, 761.644, 1208.785];
        $cona->specTransmisijskeIzgube = 143.8765034039049;
        $cona->specVentilacijskeIzgube = 8.184;


        $configSistem = <<<EOT
        {
            "vrsta": "toplovodni",
            "energent": "elektrika",
            "rezim": "40/30"
        }
        EOT;
        $sistem = new ToplovodniOgrevalniSistem($configSistem);
        $sistem->init($cona);

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
        $prenosnik = new PloskovnoOgrevalo($configPrenosnika);

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

        $preneseneIzgube = [1315.89, 821.00, 439.53, 159.18, 25.31, 0.00, 0.00, 0.00, 24.71, 213.60, 852.09, 1323.62];

        $izgube = $razvod->toplotneIzgube($preneseneIzgube, $sistem, $cona, null, ['prenosnik' => $prenosnik]);
        $roundedResult = array_map(fn($el) => round($el, 2), $izgube);

        $expected = [246.28, 162.67, 100.07, 43.58, 6.69, 0.00, 0.00, 0.00, 6.44, 58.22, 169.72, 247.51];

        $this->assertEquals($expected, $roundedResult);
    }

    /*public function testPotrebnaElektricnaEnergija(): void
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
            "vrsta": "toplavoda",
            "crpalka": {},
            "ceviHorizontaliVodi": {
            },
            "ceviDvizniVodi": {
            },
            "ceviPrikljucniVodi": {
            }
        }
        EOT;

        $razvodTSV = new RazvodTSV($config);

        $izgube = $razvodTSV->potrebnaElektricnaEnergija(null, null, $cona, null);
        $roundedResult = array_map(fn($el) => round($el, 2), $izgube);

        $expected = [6.99, 6.31, 6.99, 6.76, 6.99, 6.76, 6.99, 6.99, 6.76, 6.99, 6.76, 6.99];

        $this->assertEquals($expected, $roundedResult);
    }*/
}