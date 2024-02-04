<?php
declare(strict_types=1);

use App\Calc\Hrup\Elementi\Konstrukcija;
use App\Core\App;
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
            "gostota": 2300,
            "debelina": 0.26,
            "hitrostLongitudinalnihValov": 3500,
            "faktorNotranjegaDusenja": 0.006
        }
        EOT);
        
        $konstrukcija = new Konstrukcija($konstrukcijaConfig);
        $konstrukcija->analiza();

        $this->assertEquals(66, $konstrukcija->Rw);
    }
}