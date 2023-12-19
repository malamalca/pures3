<?php
declare(strict_types=1);

use App\Calc\Hrup\ZunanjiHrup\Fasada;
use App\Core\App;
use PHPUnit\Framework\TestCase;

class ZunanjiHrupFasadaTest extends TestCase
{
    public function testPrimerStandard12354_3(): void
    {
        $konstrukcijeLib = json_decode(<<<EOT
        [
            {
                "id": "dvojnastena",
                "naziv": "Dvojna opeka (120-50-100) mm",
                "povrsinskaMasa": 400,
                "Rw": 57,
                "C": -2,
                "Ctr": -6
            }
        ]
        EOT);
        $oknaVrataLib = json_decode(<<<EOT
        [
            {
                "id": "okno_6-12-6",
                "naziv": "Okna (les) s stekli (6-12-4) mm",
                "vrsta": "okno",
                "Rw": 33,
                "C": -1,
                "Ctr": -4,
                "dR": 0
            },
            {
                "id": "okno_6",
                "naziv": "Okno 6 mm",
                "vrsta": "okno",
                "Rw": 32,
                "C": -1,
                "Ctr": -2,
                "dR": 0
            }
        ]
        EOT);
        $maliElementiLib = json_decode(<<<EOT
        [
            {
                "id": "prezracevalnaOdprtina1m",
                "naziv": "Vstopna odprtina za zrak; 6 dm³/s, 1 m",
                "Rw": 37,
                "C": -1,
                "Ctr": -3
            }
        ]
        EOT);

        $fasada = json_decode(<<<EOT
        {
            "vplivPrometa": true,
            "deltaL_fasada": 0,
            "konstrukcije": [
                {
                    "idKonstrukcije": "dvojnastena",
                    "povrsina": 6
                }
            ],
            "oknaVrata": [
                {
                    "idOknaVrata": "okno_6-12-6",
                    "povrsina": 4.5
                },
                {
                    "idOknaVrata": "okno_6",
                    "povrsina": 0.5
                }
            ],
            "maliElementi": [
                {
                    "idMaliElement": "prezracevalnaOdprtina1m",
                    "dolzina": 3,
                    "povrsina": 0.3
                }
            ]
        }
        EOT);

        $elementiLib = new \stdClass();
        $elementiLib->konstrukcije = $konstrukcijeLib;
        $elementiLib->oknaVrata = $oknaVrataLib;
        $elementiLib->maliElementi = $maliElementiLib;
        $hrup = new Fasada($elementiLib, $fasada);

        $this->assertEquals(28.0, round($hrup->Rw, 0));
    }
}