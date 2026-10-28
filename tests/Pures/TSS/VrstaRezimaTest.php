<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS;

use App\Calc\GF\TSS\OHTSistemi\Izbire\VrstaRezima;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Radiator;
use App\Calc\GF\TSS\OHTSistemi\ToplovodniOHTSistem;
use PHPUnit\Framework\TestCase;

final class VrstaRezimaTest extends TestCase
{
    public function testRezim70_55Toplovodni(): void
    {
        $rezim = VrstaRezima::from('70/55');
        $sistem = new ToplovodniOHTSistem();

        // srednja temperatura ogrevnega medija (70 + 55) / 2
        $this->assertEquals(62.5, $rezim->projektnaTemperatura());
        $this->assertEquals(62.5, $rezim->srednjaTemperatura($sistem));
        // ΔθHK = 70 - 55
        $this->assertEquals(15, $rezim->temperaturnaRazlikaStandardnegaRezima($sistem));
    }

    public function testObstojeciRezimiNespremenjeni(): void
    {
        $sistem = new ToplovodniOHTSistem();

        $this->assertEquals([35, 35, 50], array_map(
            fn($r) => VrstaRezima::from($r)->projektnaTemperatura(),
            ['35/30', '40/30', '55/45']
        ));
        $this->assertEquals([32.5, 35, 50], array_map(
            fn($r) => VrstaRezima::from($r)->srednjaTemperatura($sistem),
            ['35/30', '40/30', '55/45']
        ));
        $this->assertEquals([5, 10, 10], array_map(
            fn($r) => VrstaRezima::from($r)->temperaturnaRazlikaStandardnegaRezima($sistem),
            ['35/30', '40/30', '55/45']
        ));
    }

    public function testRezim70_55NiPodprtPriToplotnihCrpalkah(): void
    {
        $this->expectException(\Exception::class);
        VrstaRezima::from('70/55')->temperaturaPonora();
    }

    public function testRezim70_55NiPodprtZaFaktorTC(): void
    {
        $this->expectException(\Exception::class);
        VrstaRezima::from('70/55')->faktorDeltaTempTC();
    }

    public function testRadiatorDeltaTStr70_55(): void
    {
        $okolje = new \stdClass();
        $okolje->zunanjaT = [-1, 1, 5, 9, 14, 17, 19, 19, 15, 10, 4, 0];
        $cona = new \stdClass();
        $cona->notranjaTOgrevanje = 20;

        $sistem = new ToplovodniOHTSistem();
        $sistem->ogrevanje = new \App\Calc\GF\TSS\OHTSistemi\Sistemi\Ogrevanje();
        $sistem->ogrevanje->rezim = VrstaRezima::Rezim_70_55;

        // Δθstr = (Δθstr,1 + Δθstr,2) / 2; Δθstr,1 = 0,7 K (nadtemperatura 42,5 K), Δθstr,2 = 0,3 K (zunanja stena)
        $radiator = new Radiator(json_decode(
            '{"id": "RAD", "vrsta": "radiatorji", "namestitev": "zunanjeStene", ' .
            '"hidravlicnoUravnotezenje": "staticnoDviznihVodov", "regulacijaTemperature": "P-krmilnik", ' .
            '"mocRegulatorja": 1, "steviloRegulatorjev": 1}'
        ));
        $radiator->toplotneIzgube(
            [1206.707, 746.368, 390.117, 135.734, 19.220, 0.0, 0.0, 0.0, 17.903, 179.496, 761.644, 1208.785],
            $sistem,
            $cona,
            $okolje,
            ['namen' => 'ogrevanje']
        );

        $this->assertEqualsWithDelta(0.5, $radiator->deltaT_str, 1e-9);
    }
}
