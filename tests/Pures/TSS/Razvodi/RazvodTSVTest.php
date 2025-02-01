<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\Razvodi;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire\VrstaRazvodnihCevi;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\RazvodTSV;
use PHPUnit\Framework\TestCase;

final class RazvodTSVTest extends TestCase
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

        $dolzina = $razvodTSV->dolzinaCevi(VrstaRazvodnihCevi::HorizontalniRazvod, $cona);
        $this->assertEquals(21, $dolzina);

        $dolzina = $razvodTSV->dolzinaCevi(VrstaRazvodnihCevi::DvizniVod, $cona);
        $this->assertEquals(54, $dolzina);

        $dolzina = $razvodTSV->dolzinaCevi(VrstaRazvodnihCevi::PrikljucniVod, $cona);
        $this->assertEquals(18, $dolzina);
    }

    public function testHidravlicnaMocCrpalke(): void
    {
        $sistem = new \stdClass();

        $cona = new \stdClass();
        $cona->dolzina = 10;
        $cona->sirina = 8;
        $cona->steviloEtaz = 3;
        $cona->etaznaVisina = 3;
        $cona->notranjaTOgrevanje = 20;

        $okolje = new \stdClass();
        $okolje->projektnaZunanjaT = -13;

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

        $hidravlicnaMoc = $razvodTSV->izracunHidravlicneMoci($cona, $okolje);

        $this->assertEquals(0.844, round($hidravlicnaMoc, 3));
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

        $okolje = new \stdClass();
        $okolje->projektnaZunanjaT = -13;

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

        $faktor_fe = $razvodTSV->izracunFaktorjaRabeEnergijeCrpalke($cona, $okolje);

        $this->assertEquals(16.642, round($faktor_fe, 3));
    }

    public function testCasDelovanjaCrpalke(): void
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

        $casH = $razvodTSV->izracunCasaDelovanjaCrpalke($cona);

        $this->assertEquals(13.484, round($casH, 3));
    }

    public function testToplotneIzgube(): void
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

        $izgube = $razvodTSV->toplotneIzgube(null, null, $cona, null);
        $roundedResult = array_map(fn($el) => round($el, 2), $izgube);

        $expected = [457.26, 413.00, 457.26, 442.51, 457.26, 442.51, 457.26, 457.26, 442.51, 457.26, 442.51, 457.26];

        $this->assertEquals($expected, $roundedResult);
    }

    public function testPotrebnaElektricnaEnergija(): void
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
    }
}
