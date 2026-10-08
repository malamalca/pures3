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

    public function testToplotneIzgubeSPodatkomProizvajalca(): void
    {
        $cona = new \stdClass();
        $cona->notranjaTOgrevanje = 20;

        $config = <<<EOT
        {
            "id": "TSV",
            "vrsta": "posrednoOgrevan",
            "volumen": 230,
            "stalneIzgube": 58,
            "istiProstorKotGrelnik": true,
            "znotrajOvoja": true
        }
        EOT;

        $hranilnik = new PosrednoOgrevanHranilnik($config);
        $izgube = $hranilnik->toplotneIzgube([], null, $cona, null, ['namen' => 'tsv']);

        // 58 W = 1,392 kWh/24h pri 45 K; f_povezava 1,2; (50 - 20) / 45; 365 dni
        $this->assertEqualsWithDelta(1.392 * 1.2 * 30 / 45 * 365, array_sum($izgube['tsv']), 0.01);

        // dnevneIzgube ima prednost pred stalneIzgube
        $config2 = json_decode($config);
        $config2->dnevneIzgube = 1.0;
        $hranilnik2 = new PosrednoOgrevanHranilnik($config2);
        $izgube2 = $hranilnik2->toplotneIzgube([], null, $cona, null, ['namen' => 'tsv']);
        $this->assertEqualsWithDelta(1.0 * 1.2 * 30 / 45 * 365, array_sum($izgube2['tsv']), 0.01);

        // brez podatka proizvajalca ostane izračun po enačbi 123b
        $config3 = json_decode($config);
        unset($config3->stalneIzgube);
        $hranilnik3 = new PosrednoOgrevanHranilnik($config3);
        $izgube3 = $hranilnik3->toplotneIzgube([], null, $cona, null, ['namen' => 'tsv']);
        $this->assertEqualsWithDelta((0.8 + 0.02 * pow(230, 0.77)) * 1.2 * 30 / 45 * 365, array_sum($izgube3['tsv']), 0.01);
    }
}
