<?php
declare(strict_types=1);

use App\Calc\GF\Cone\ElementiOvoja\NetransparentenElementOvoja;
use PHPUnit\Framework\TestCase;

final class ProtiNeogrevaniKletiTest extends TestCase
{
    public function testValidacijaTSG(): void
    {
        $okolje = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'okolje.json'));

        /** @var array $coneIn */
        $coneIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'cone.json'));
        $cona = $coneIn[0];

        $konstrukcije = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'konstrukcije' . DS . 'netransparentne.json'));

        $konstrukcija = $konstrukcije[1];
        $konstrukcija->TSG->tip = 'tla-neogrevano';
        $konstrukcija->vrsta = 13;
        $konstrukcija->Rsi = 0.0;
        $konstrukcija->Rse = 0.1;
        $konstrukcija->U = 0.17993320; // to je zaradi drugačnih Rsi/Rse

        $elementOvoja = json_decode(<<<PRN
        {
            "idKonstrukcije": "Tp1",
            "povrsina": 200,
            "obseg": 40,
            "debelinaStene": 0.4,
            "globina": 2,
            "lambdaTla": 2,
            "U_tla": 0.2,
            "U_zid": 0.3,
            "U_zid_nadTerenom": 0.1,
            "visinaNadTerenom": 1,
            "prostorninaKleti": 400,
            "izmenjavaZraka": 0.3
        }
        PRN);
        $elementOvoja = new NetransparentenElementOvoja($konstrukcija, $elementOvoja);
        $elementOvoja->analiza($cona, $okolje);

        $this->assertEquals(0.13521, round($elementOvoja->U, 5));
        $this->assertEquals(30.89981, round($elementOvoja->Lpi, 5));
        $this->assertEquals(7.80707, round($elementOvoja->Lpe, 5));
    }
}
