<?php
declare(strict_types=1);

namespace App\Test\Hrup;

use App\Calc\Hrup\OdmevniHrup\Prostor;
use App\Core\View;
use PHPUnit\Framework\TestCase;

class OdmevniHrupTest extends TestCase
{
    /**
     * Neodvisen računski primer s tremi skupinami absorpcije.
     *
     * @return \stdClass
     */
    private function konfiguracija(): \stdClass
    {
        return json_decode('{
            "id":"P1", "naziv":"Prostor", "vrsta":"ucilnica", "zasedenost":"polna",
            "prostornina":"10*5*6", "absorpcijaZraka":0.001, "metoda":"sabine",
            "mejniElementi":[
                {"id":"strop", "naziv":"Strop", "povrsina":50, "alfa":0.1},
                {"id":"ostalo", "naziv":"Ostalo", "povrsina":150, "alfa":0.1}
            ],
            "povrsinskoPohistvo":[
                {"id":"stoli", "naziv":"Stoli", "povrsina":20, "alfa":0.5, "prostornina":4}
            ],
            "posamezniElementi":[
                {"id":"omare", "naziv":"Omare", "stevilo":2, "absorpcijskaPovrsina":3, "prostornina":3}
            ],
            "absorberji":[
                {"id":"a1", "naziv":"Obloga 1", "idElementa":"strop", "povrsina":10, "alfa":0.8},
                {"id":"a2", "naziv":"Obloga 2", "idElementa":"strop", "povrsina":20, "alfa":0.8}
            ]
        }');
    }

    public function testAbsorpcijaInZamenjavaPovrsin(): void
    {
        $config = $this->konfiguracija();
        $izvor = json_encode($config);
        $prostor = new Prostor($config);
        $prostor->analiza();
        // Vzrak=300-4-2*3=290; A1=20+10+6+4*0.001*290=37.16.
        // A2=37.16-30*0.1+30*0.8=58.16; S ostane 200.
        $this->assertEqualsWithDelta(290, $prostor->pred->prostorninaZraka, 1e-9);
        $this->assertEqualsWithDelta(37.16, $prostor->pred->absorpcijskaPovrsina[500], 1e-9);
        $this->assertEqualsWithDelta(58.16, $prostor->po->absorpcijskaPovrsina[500], 1e-9);
        $this->assertEqualsWithDelta(1.27206673843, $prostor->pred->odmevniCas[500], 1e-9);
        $this->assertEqualsWithDelta(0.812757909216, $prostor->po->odmevniCas[500], 1e-9);
        $this->assertEquals(200, $prostor->po->povrsinaMejnihElementov);
        $this->assertEquals(20, $prostor->po->elementi['strop']['povrsina']);
        $this->assertEqualsWithDelta(10 * log10(58.16 / 37.16), $prostor->znizanjeHrupa[500], 1e-9);
        $this->assertSame($izvor, json_encode($config));
        $prvic = json_encode($prostor->export());
        $prostor->analiza();
        $this->assertSame($prvic, json_encode($prostor->export()));
    }

    public function testPovrsinskoPohistvoJeLahkoNosilec(): void
    {
        $config = $this->konfiguracija();
        $config->absorberji = [(object)[
            'id' => 'prevleka', 'naziv' => 'Prevleka', 'idElementa' => 'stoli', 'povrsina' => 20, 'alfa' => 0.9,
        ]];
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertEqualsWithDelta(45.16, $prostor->po->absorpcijskaPovrsina[500], 1e-9);
        $this->assertEquals(0, $prostor->po->elementi['stoli']['povrsina']);
    }

    public function testEyringInSamodejnaIzbira(): void
    {
        $config = $this->konfiguracija();
        $config->metoda = 'samodejno';
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertSame('sabine', $prostor->pred->metoda[500]);
        $this->assertSame('eyring', $prostor->po->metoda[500]);
        $this->assertEqualsWithDelta(47.27 / (-200 * log(0.795) + 17.16), $prostor->po->odmevniCas[500], 1e-9);
    }

    public function testNespremenjenProstorInNicelnaAbsorpcija(): void
    {
        $config = $this->konfiguracija();
        $config->absorberji = [];
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertEquals($prostor->pred, $prostor->po);
        $this->assertEquals(0, $prostor->znizanjeHrupa[500]);
        $config->posamezniElementi = [];
        $config->povrsinskoPohistvo = [];
        $config->absorpcijaZraka = 0;
        foreach ($config->mejniElementi as $element) {
            $element->alfa = 0;
        }
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertNull($prostor->po->odmevniCas[500]);
        $this->assertNull($prostor->skladnost->ustreza);
        $this->assertNotFalse(json_encode($prostor->export()));
    }

    public function testVseVrsteProstorov(): void
    {
        $cilji = [
            'ucilnica' => 0.32 * log10(300) - 0.17,
            'glasbenaUcilnica' => 0.45 * log10(300) + 0.07,
            'vecnamenskiPouk' => 0.6, 'vecnamenskiGovor' => 0.8, 'vecnamenskiGlasba' => 1.2,
            'sportniProstor1' => 1.27 * log10(300) - 2.49,
            'sportniProstor2' => 0.95 * log10(300) - 1.74,
        ];
        foreach ($cilji as $vrsta => $cilj) {
            $config = $this->konfiguracija();
            $config->vrsta = $vrsta;
            $config->zasedenost = str_starts_with($vrsta, 'sportni') ? 'prazna' : 'polna';
            $prostor = new Prostor($config);
            $prostor->analiza();
            $this->assertEqualsWithDelta($cilj, $prostor->skladnost->optimalniCas, 1e-9, $vrsta);
            $this->assertNotNull($prostor->skladnost->ustreza);
        }
    }

    public function testFrekvencnaPresojaInZasedenost(): void
    {
        $config = $this->konfiguracija();
        $prostor = new Prostor($config);
        $prostor->analiza();
        $cilj = $prostor->skladnost->optimalniCas;
        $this->assertEqualsWithDelta(0.65 * $cilj, $prostor->skladnost->pasovi[125]->min, 1e-9);
        $this->assertEqualsWithDelta(1.2 * $cilj, $prostor->skladnost->pasovi[500]->max, 1e-9);
        $this->assertFalse($prostor->skladnost->ustreza);
        $config->zasedenost = 'prazna';
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertNull($prostor->skladnost->ustreza);
        $config->vrsta = 'vecnamenskiPouk';
        $config->prostornina = 1800;
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertNull($prostor->skladnost->optimalniCas);
    }

    public function testNeveljavniVhodi(): void
    {
        $spremembe = [
            fn($c) => $c->absorberji[1]->povrsina = 41,
            fn($c) => $c->absorberji[0]->idElementa = 'neznan',
            fn($c) => $c->absorberji[0]->id = 'strop',
            fn($c) => $c->mejniElementi[0]->alfa = 1.1,
            fn($c) => $c->mejniElementi[0]->alfa = (object)['500' => 0.2],
            fn($c) => $c->prostornina = '-1',
            fn($c) => $c->prostornina = 0,
            fn($c) => $c->posamezniElementi[0]->prostornina = 200,
            fn($c) => $c->posamezniElementi[0]->stevilo = -1,
            fn($c) => $c->mejniElementi = [],
            fn($c) => $c->mere = (object)['dolzina' => 0, 'sirina' => 5, 'visina' => 6],
            fn($c) => $c->mere = (object)['dolzina' => '1-1', 'sirina' => 5, 'visina' => 6],
        ];
        foreach ($spremembe as $sprememba) {
            $config = $this->konfiguracija();
            $sprememba($config);
            try {
                $prostor = new Prostor($config);
                $prostor->analiza();
                $this->fail('Neveljaven vhod ni bil zavrnjen.');
            } catch (\InvalidArgumentException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    public function testPrimerJsonInPrikaz(): void
    {
        $config = json_decode((string)file_get_contents(PROJECTS . 'Hrup/TestniProjekt/podatki/odmevniHrup.json'))[0];
        $prostor = new Prostor($config);
        $prostor->analiza();
        $izvoz = json_decode((string)json_encode($prostor->export()));
        $view = new View(['projectId' => 'TestniProjekt', 'prostor' => $izvoz]);
        $view->area = 'Hrup';
        $view->layout = false;
        $html = (string)$view->render('OdmevniHrup', 'view');
        $this->assertStringContainsString('Strop', $html);
        $this->assertStringContainsString('4000 Hz', $html);
        $this->assertStringContainsString('USTREZNOST', $html);
        $this->assertLessThan($izvoz->pred->srednjiOdmevniCas, $izvoz->po->srednjiOdmevniCas);
    }

    public function testNeuporabenModelNePotrdiPosameznihOktav(): void
    {
        $config = $this->konfiguracija();
        $config->posamezniElementi[0]->prostornina = 40;
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertFalse($prostor->po->modelUporaben);
        $this->assertNull($prostor->skladnost->ustreza);
        foreach ($prostor->skladnost->pasovi as $pas) {
            $this->assertNull($pas->ustreza);
        }
    }

    public function testNapacnaZasedenostNePotrdiPosameznihOktav(): void
    {
        $config = $this->konfiguracija();
        $config->zasedenost = 'prazna';
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertNull($prostor->skladnost->ustreza);
        foreach ($prostor->skladnost->pasovi as $pas) {
            $this->assertNull($pas->ustreza);
        }
    }

    public function testPrekoracitevObNedolocljivemPasu(): void
    {
        foreach (['ucilnica', 'lastna'] as $vrsta) {
            foreach ([125, 4000] as $nicelniPas) {
                $config = $this->konfiguracija();
                $config->vrsta = $vrsta;
                $config->nazivVrste = 'Predavalnica';
                $config->mejniOdmevniCas = (object)array_fill_keys(Prostor::FREKVENCE, 0.1);
                $config->absorpcijaZraka = 0;
                $config->absorberji = $config->posamezniElementi = $config->povrsinskoPohistvo = [];
                foreach ($config->mejniElementi as $element) {
                    $element->alfa = (object)array_fill_keys(Prostor::FREKVENCE, 0.01);
                    $element->alfa->{(string)$nicelniPas} = 0;
                }
                $prostor = new Prostor($config);
                $prostor->analiza();
                $this->assertNull($prostor->skladnost->pasovi[$nicelniPas]->ustreza);
                $this->assertFalse($prostor->skladnost->pasovi[500]->ustreza);
                $this->assertFalse($prostor->skladnost->ustreza);
            }
        }
    }

    public function testMejeInNapakaIzvenSrednjihFrekvenc(): void
    {
        $config = $this->konfiguracija();
        $config->absorpcijaZraka = 0;
        $config->povrsinskoPohistvo = [];
        $config->posamezniElementi = [];
        $config->absorberji = [];
        $topt = 0.32 * log10(300) - 0.17;
        $alfa = (object)array_fill_keys(Prostor::FREKVENCE, 0.163 * 300 / (200 * $topt));
        $alfa->{'500'} /= 0.8;
        $alfa->{'1000'} /= 1.2;
        foreach ($config->mejniElementi as $element) {
            $element->alfa = $alfa;
        }
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertTrue($prostor->skladnost->ustreza);
        $this->assertEqualsWithDelta($topt, $prostor->po->srednjiOdmevniCas, 1e-9);
        $alfa->{'125'} /= 1.3;
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertFalse($prostor->skladnost->pasovi[125]->ustreza);
        $this->assertFalse($prostor->skladnost->ustreza);
        $this->assertEqualsWithDelta($topt, $prostor->po->srednjiOdmevniCas, 1e-9);
    }

    public function testPopolnaAbsorpcijaInOsebeVSportnemProstoru(): void
    {
        $config = $this->konfiguracija();
        $config->vrsta = 'sportniProstor1';
        $config->zasedenost = 'prazna';
        $config->posamezniElementi[0]->vrsta = 'osebe';
        $config->absorberji = [];
        $config->metoda = 'eyring';
        foreach ($config->mejniElementi as $element) {
            $element->alfa = 1;
        }
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertEquals(0, $prostor->po->odmevniCas[500]);
        $this->assertNull($prostor->znizanjeHrupa[500]);
        $this->assertNull($prostor->skladnost->ustreza);
        $this->assertNotFalse(json_encode($prostor->export()));
    }
}
