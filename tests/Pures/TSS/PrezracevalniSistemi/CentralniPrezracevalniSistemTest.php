<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS\PrezracevalniSistemi;

use App\Calc\GF\TSS\PrezracevalniSistemi\CentralniPrezracevalniSistem;
use PHPUnit\Framework\TestCase;

final class CentralniPrezracevalniSistemTest extends TestCase
{
    public function testToplotneIzgube(): void
    {
        $cona = new \stdClass();
        $cona->id = 'Cona1';

        $config = <<<EOT
        {
            "id": "Prezracevanje",
            "idCone": "Cona1",
            "vrsta": "centralni",
            "razredH1H2": true,
            "mocSenzorjev": 1,
            "odvod": {
                "filter": "hepa"
            },
            "dovod": {
                "filter": "hepa"
            },
            "volumenProjekt": 200
        }
        EOT;

        $sistem = new CentralniPrezracevalniSistem($config);

        $sistem->analiza([], $cona, null);

        $izgubeRazvoda = $sistem->potrebnaEnergija;
        $roundedResult = array_map(fn($el) => round($el, 2), $izgubeRazvoda);
        $expected = [160.74, 145.18, 160.74, 155.55, 160.74, 155.55, 160.74, 160.74, 155.55, 160.74, 155.55, 160.74];
        $this->assertEquals($expected, $roundedResult);
    }
}
