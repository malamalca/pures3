<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\Hranilniki;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Hranilniki\PosrednoOgrevanHranilnik;
use PHPUnit\Framework\TestCase;

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
            "znotrajOvoja": true
        }
        EOT;

        $hranilnik = new PosrednoOgrevanHranilnik($config);

        $izgube = $hranilnik->toplotneIzgube([], null, $cona, null, ['namen' => 'tsv']);
        $roundedResult = array_map(fn($el) => round($el, 2), $izgube['tsv']);

        $expected = [54.67, 49.38, 54.67, 52.90, 54.67, 52.90, 54.67, 54.67, 52.90, 54.67, 52.90, 54.67];

        $this->assertEquals($expected, $roundedResult);
    }
}
