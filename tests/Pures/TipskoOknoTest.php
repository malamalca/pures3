<?php
declare(strict_types=1);

namespace App\Test\Pures;

use App\Lib\CalcKonstrukcije;
use PHPUnit\Framework\TestCase;

final class TipskoOknoTest extends TestCase
{
    public function testIzracunUwTipskegaOkna(): void
    {
        $konstrukcijaJson = <<<EOT
        {
            "id": "O1",
            "naziv": "Okno, 3s zasteklitev",
            "vrsta": 0,
            "Ug": 0.5,
            "Uf": 0.9,
            "Psi": 0.04,
            "sirinaOkvirja": 0.1
        }
        EOT;

        $result = CalcKonstrukcije::transparentne(json_decode($konstrukcijaJson), new \stdClass());

        // tipsko okno po SIST EN ISO 10077-1: 1,23 m x 1,48 m
        $this->assertEquals(1.23, $result->tipskoOkno->sirina);
        $this->assertEquals(1.48, $result->tipskoOkno->visina);

        // zasteklitev 1,03 m x 1,28 m
        $this->assertEquals(1.3184, round($result->tipskoOkno->Ag, 4));
        $this->assertEquals(0.502, round($result->tipskoOkno->Af, 4));
        $this->assertEquals(4.62, round($result->tipskoOkno->lg, 4));
        $this->assertEquals(0.276, round($result->tipskoOkno->delezOkvirja, 3));

        // U_w = (1,3184*0,5 + 0,502*0,9 + 4,62*0,04) / 1,8204
        $this->assertEquals(0.712, round($result->Uw_tip, 3));
        $this->assertTrue(round($result->Uw_tip, 2) <= $result->TSG->Umax);
    }

    public function testPodanUwImaPrednost(): void
    {
        $konstrukcijaJson = <<<EOT
        {
            "id": "O2",
            "naziv": "Okno s podanim Uw",
            "vrsta": 0,
            "Uw": 0.85,
            "g": 0.5
        }
        EOT;

        $result = CalcKonstrukcije::transparentne(json_decode($konstrukcijaJson), new \stdClass());

        $this->assertObjectNotHasProperty('Uw_tip', $result);
        $this->assertObjectNotHasProperty('tipskoOkno', $result);
    }

    public function testVrataNimajoTipskegaOkna(): void
    {
        $konstrukcijaJson = <<<EOT
        {
            "id": "V1",
            "naziv": "Vhodna vrata",
            "vrsta": 2,
            "Ud": 1
        }
        EOT;

        $result = CalcKonstrukcije::transparentne(json_decode($konstrukcijaJson), new \stdClass());

        $this->assertObjectNotHasProperty('Uw_tip', $result);
    }

    public function testBrezSirineOkvirjaNiIzracuna(): void
    {
        $konstrukcijaJson = <<<EOT
        {
            "id": "O3",
            "naziv": "Okno brez podane sirine okvirja",
            "vrsta": 0,
            "Ug": 0.6,
            "Uf": 1.0,
            "Psi": 0.04
        }
        EOT;

        $result = CalcKonstrukcije::transparentne(json_decode($konstrukcijaJson), new \stdClass());

        $this->assertObjectNotHasProperty('Uw_tip', $result);
    }
}
