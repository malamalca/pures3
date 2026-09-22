<?php
declare(strict_types=1);

namespace App\Test\Hrup;

use App\Calc\Hrup\OdmevniHrup\Absorpcija;
use App\Calc\Hrup\OdmevniHrup\Prostor;
use PHPUnit\Framework\TestCase;

class OdmevniHrupStandardTest extends TestCase
{
    /**
     * Dodatek E: mere in zaokrožene površine, kot so objavljene v standardu.
     *
     * @return \stdClass
     */
    private function primerE(): \stdClass
    {
        return json_decode('{
            "id":"E", "naziv":"Dodatek E", "vrsta":"lastna", "nazivVrste":"Testni prostor",
            "mejniOdmevniCas":1.0, "zasedenost":"prazna", "prostornina":29.75,
            "metoda":"en12354", "absorpcijaZraka":0,
            "mere":{"dolzina":4.54,"sirina":2.73,"visina":2.40},
            "mejniElementi":[
                {"id":"tla","naziv":"Tla","ploskev":"z0","povrsina":12.39,"idAbsorpcije":"B.1.3"},
                {"id":"strop","naziv":"Strop","ploskev":"z1","povrsina":12.39,"idAbsorpcije":"B.1.1"},
                {"id":"stena","naziv":"Stena","ploskev":"y0","povrsina":10.90,"idAbsorpcije":"B.1.2"},
                {"id":"fasada","naziv":"Fasada","ploskev":"y1","povrsina":10.90,"idAbsorpcije":"B.1.7"},
                {"id":"bok1","naziv":"Bok 1","ploskev":"x0","povrsina":6.55,"idAbsorpcije":"B.1.2"},
                {"id":"bok2","naziv":"Bok 2","ploskev":"x1","povrsina":6.55,"idAbsorpcije":"B.1.2"}
            ]
        }');
    }

    public function testDodatekEPrazenProstor(): void
    {
        $prostor = new Prostor($this->primerE());
        $prostor->analiza();
        $this->assertEqualsWithDelta(2.2633, $prostor->pred->absorpcijskaPovrsina[1000], 1e-9);
        $this->assertEquals(2.3, round($prostor->pred->absorpcijskaPovrsina[1000], 1));
        $this->assertEquals(2.1, round($prostor->pred->odmevniCas[1000], 1));
        $this->assertEquals(29.75, $prostor->pred->prostorninaZraka);
    }

    public function testDodatekETrdiPredmeti(): void
    {
        $config = $this->primerE();
        $config->posamezniElementi = [];
        foreach (['miza' => [0.15, 1], 'pisalnaMiza' => [0.6, 1], 'stol' => [0.05, 2], 'omara' => [0.65, 2]] as $id => $v) {
            $config->posamezniElementi[] = (object)[
                'id' => $id, 'naziv' => $id, 'prostornina' => $v[0], 'stevilo' => $v[1], 'ocenaIzProstornine' => true,
            ];
        }
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertEqualsWithDelta(0.072, $prostor->pred->delezProstorninePredmetov, 0.001);
        $this->assertEqualsWithDelta(5.03, $prostor->pred->absorpcijskaPovrsina[1000], 0.01);
        $this->assertEquals(0.9, round($prostor->pred->odmevniCas[1000], 1));
    }

    public function testDodatekEAbsorpcijskaStenaInD2(): void
    {
        $config = $this->primerE();
        $config->absorberji = [(object)[
            'id' => 'obloga', 'naziv' => 'Obloga', 'idElementa' => 'stena', 'povrsina' => 9.81, 'alfa' => 0.85,
        ]];
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertEqualsWithDelta(10.2094, $prostor->po->absorpcijskaPovrsina[1000], 1e-9);
        $this->assertEquals(0.5, round($prostor->po->odmevniCas[1000], 1));
        $config->metoda = 'en12354D2';
        $prostor = new Prostor($config);
        $prostor->analiza();
        $polja = $prostor->po->d2->polja[1000];
        $this->assertEqualsWithDelta(13.69, $polja->Aefektivna['x'], 0.02);
        $this->assertEqualsWithDelta(2.04, $polja->Aefektivna['y'], 0.02);
        $this->assertEqualsWithDelta(13.22, $polja->Aefektivna['z'], 0.02);
        $this->assertEqualsWithDelta(10.21, $polja->Aefektivna['d'], 0.01);
        $this->assertEqualsWithDelta(0.35, $polja->T['x'], 0.01);
        $this->assertEqualsWithDelta(2.34, $polja->T['y'], 0.02);
        $this->assertEqualsWithDelta(0.36, $polja->T['z'], 0.01);
        $this->assertEqualsWithDelta(0.47, $polja->T['d'], 0.01);
        $this->assertEquals(0.9, round($prostor->po->odmevniCas[1000], 1));
        $this->assertSame('nizkeFrekvence', $prostor->po->d2->polja[500]->rezim);
        $this->assertSame('visokeFrekvence', $polja->rezim);
    }

    public function testKnjiznicaInPrivzetiZrak(): void
    {
        $knjiznica = Absorpcija::knjiznica();
        $this->assertCount(13, $knjiznica->povrsine);
        $this->assertCount(5, $knjiznica->predmeti);
        $this->assertCount(6, $knjiznica->razporeditve);
        $config = $this->primerE();
        unset($config->absorpcijaZraka);
        $config->posamezniElementi = [(object)[
            'id' => 'oseba', 'naziv' => 'Oseba', 'stevilo' => 2, 'idAbsorpcije' => 'C.1.5',
        ]];
        $config->povrsinskoPohistvo = [(object)[
            'id' => 'stoli', 'naziv' => 'Stoli', 'povrsina' => 10, 'idAbsorpcije' => 'C.2.1',
        ]];
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertEqualsWithDelta(0.119, $prostor->pred->absorpcijaZraka[1000], 1e-9);
        $this->assertEqualsWithDelta(0.4879, $prostor->pred->absorpcijaZraka[4000], 1e-9);
        $this->assertEqualsWithDelta(2.8, $prostor->pred->elementi['oseba']['absorpcijskaPovrsina'][4000], 1e-9);
        $this->assertEqualsWithDelta(1.2, $prostor->pred->elementi['stoli']['absorpcijskaPovrsina'][1000], 1e-9);
        $this->assertSame('osebe', $prostor->posamezniElementi[0]->vrsta);
        $this->assertStringContainsString('C.1', $prostor->posamezniElementi[0]->vir);
    }

    public function testLastnaMeja(): void
    {
        $config = $this->primerE();
        $config->nazivVrste = 'Hodnik';
        $config->mejniOdmevniCas = 3;
        foreach ($config->mejniElementi as $element) {
            unset($element->idAbsorpcije);
            $element->alfa = 0.1;
        }
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertTrue($prostor->skladnost->ustreza);
        $this->assertSame(3.0, $prostor->export()->projektnaVrednost);
        $config->mejniOdmevniCas = 0.5;
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertFalse($prostor->skladnost->ustreza);
        $config->mejniOdmevniCas = (object)array_fill_keys(Prostor::FREKVENCE, 10);
        $config->mejniOdmevniCas->{'125'} = 0.1;
        $prostor = new Prostor($config);
        $prostor->analiza();
        $this->assertFalse($prostor->skladnost->ustreza);
        $this->assertTrue($prostor->skladnost->pasovi[1000]->ustreza);
    }

    public function testD2OdmitaNeustreznoGeometrijo(): void
    {
        $config = $this->primerE();
        $config->metoda = 'en12354D2';
        $config->mejniElementi[0]->ploskev = 'x0';
        $this->expectException(\InvalidArgumentException::class);
        (new Prostor($config))->analiza();
    }
}
