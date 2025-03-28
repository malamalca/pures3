<?php
declare(strict_types=1);

namespace App\Test\Hrup\Elementi;

use App\Calc\Hrup\Elementi\Konstrukcija;
use PHPUnit\Framework\TestCase;

class KonstrukcijaTest extends TestCase
{
    public function testPrimerStandard12354_1(): void
    {
        $konstrukcijaConfig = json_decode(<<<EOT
        {
            "id": "T.1",
            "naziv": "Testni primer",
            "povrsinskaMasa": 598,
            "lastnosti": {
                "gostota": 2300,
                "debelina": 0.26,
                "hitrostLongitudinalnihValov": 3500,
                "faktorNotranjegaDusenja": 0.006
            }
        }
        EOT);

        $konstrukcija = new Konstrukcija($konstrukcijaConfig);
        $konstrukcija->analiza();

        $this->assertEquals(62, $konstrukcija->Rw);
    }
}
