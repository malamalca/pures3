<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Calc\TSS\OgrevalniSistemi\Podsistemi\Hranilniki\PosrednoOgrevanHranilnik;

final class PosrednoOgrevanHranilnikTest extends TestCase
{
    public function testToplotneIzgube(): void
    {
        $cona = new \stdClass();
        $cona->notranjaTOgrevanje = 20;

        $config = <<<EOT
        {
            "id": "TSV",
            "vrsta": "posrednoogrevan",
            "volumen": 250,
            "istiProstorKotGrelnik": true,
            "vOgrevanemProstoru": true
        }
        EOT;

        $razvod = new PosrednoOgrevanHranilnik($config);

        $izgube = $razvod->toplotneIzgube([], null, $cona, null);
        $roundedResult = array_map(fn($el) => round($el, 2), $izgube);

        $expected = [54.67, 49.38, 54.67, 52.90, 54.67, 52.90, 54.67, 54.67, 52.90, 54.67, 52.90, 54.67];

        $this->assertEquals($expected, $roundedResult);
    }
}