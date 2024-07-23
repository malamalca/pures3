<?php
declare(strict_types=1);

use App\Calc\Hrup\UdarniHrup\UdarniHrupPoenostavljen;
use App\Core\App;
use PHPUnit\Framework\TestCase;

class UdarniHrupTest extends TestCase
{
    public function testPrimerStandard12354_2(): void
    {
        $konstrukcija = json_decode(<<<EOT
        {
            "id": "T.1",
            "naziv": "Talna konstrukcija",
            "tip": "horizontalna",
            "povrsinskaMasa": 322,
            "Lnw": 76,
            "dodatniSloji": [
                {
                    "id": "estrih",
                    "vrsta": "elasticen",
                    "naziv": "estrih",
                    "povrsinskaMasa": 80,
                    "dinamicnaTogost": 8
                }
            ]
        }
        EOT);
        $konstrukcijeLib = [$konstrukcija];

        $locilnaKonstrukcijaConfig = json_decode(<<<EOT
        {
            "id": "T.1",
            "naziv": "Testna konstrukcija",
            "model": "poenostavljen",
            "idKonstrukcije": "T.1",
            "povrsinskaMasaStranskihElementov": 145,
            "prostorninaSprejemnegaProstora": 50,
            "minLnw": 50,
            "idDodatnegaSloja": "estrih"
        }
        EOT);

        $hrup = new UdarniHrupPoenostavljen($konstrukcijeLib, $locilnaKonstrukcijaConfig);
        $hrup->analiza();

        $this->assertEquals(76, round($hrup->Lnweq, 0));
        $this->assertEquals(33, $hrup->deltaL);
        $this->assertEquals(2, $hrup->K);
        $this->assertEquals(45, round($hrup->Lnw, 0));
    }
}