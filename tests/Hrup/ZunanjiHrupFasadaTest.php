<?php
declare(strict_types=1);

namespace App\Test\Hrup;

use App\Calc\Hrup\ZunanjiHrup\Fasada;
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
                "R": {"500":57},
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
                "R": {"500":33},
                "C": -1,
                "Ctr": -4,
                "dR": 0
            },
            {
                "id": "okno_6",
                "naziv": "Okno 6 mm",
                "vrsta": "okno",
                "Rw": 32,
                "R": {"500":32},
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
                "R": {"500":37},
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

    /**
     * Sestavi fasado z enim tipom stene in enim tipom okna.
     *
     * @param array $konstrukcija ["povrsina" => float, "stevilo" => int]
     * @param array $okno ["povrsina" => float, "stevilo" => int]
     * @return \App\Calc\Hrup\ZunanjiHrup\Fasada
     */
    private function fasada(array $konstrukcija, array $okno): Fasada
    {
        $elementiLib = new \stdClass();
        $elementiLib->konstrukcije = json_decode(
            '[{"id":"stena","naziv":"Stena","povrsinskaMasa":400,"Rw":57,"R":{"500":57},"C":-2,"Ctr":-6}]'
        );
        $elementiLib->oknaVrata = json_decode(
            '[{"id":"okno","naziv":"Okno","vrsta":"okno","Rw":33,"R":{"500":33},"C":-1,"Ctr":-4,"dR":0}]'
        );
        $elementiLib->maliElementi = [];

        $config = new \stdClass();
        $config->vplivPrometa = true;
        $config->deltaL_fasada = 0;
        $config->oblikaFasade = 'ravna';
        $config->konstrukcije = [(object)(['idKonstrukcije' => 'stena'] + $konstrukcija)];
        $config->oknaVrata = [(object)(['idOknaVrata' => 'okno'] + $okno)];

        return new Fasada($elementiLib, $config);
    }

    /**
     * Več enakih elementov, zapisanih s "stevilo", mora dati enak rezultat kot
     * en element s skupno površino. Prispevek elementa k tau je
     * (S * n / Sf) * 10^(-R/10) -- "stevilo" nastopa samo enkrat.
     *
     * @return void
     */
    public function testSteviloNiUpostevanoDvakrat(): void
    {
        $skupno = $this->fasada(
            ['povrsina' => 6.0, 'stevilo' => 1],
            ['povrsina' => 5.0, 'stevilo' => 1]
        );
        $razdeljeno = $this->fasada(
            ['povrsina' => 3.0, 'stevilo' => 2],
            ['povrsina' => 2.5, 'stevilo' => 2]
        );

        $this->assertEquals(11.0, $skupno->povrsina);
        $this->assertEquals(11.0, $razdeljeno->povrsina);
        $this->assertEqualsWithDelta($skupno->Rw, $razdeljeno->Rw, 0.0001);
    }

    /**
     * Kontrolna vrednost, izračunana ročno po SIST EN 12354-3:
     *
     *     tau = Sum( S_i * n_i / Sf * 10^(-(R_i + Ctr_i)/10) )
     *
     * Stena: Rw 57, m' 400 kg/m2. ZunanjaKonstrukcija Ctr ne prevzame iz
     * knjižnice, ampak ga pri enopasovnem R izračuna iz površinske mase
     * (Calc::Ctr): round(16 - 9*log10(400)) = -7, torej R_c = 50 dB.
     * Okno: Rw 33, Ctr -4 (dR = 0, zato korekcija vgradnje ni uporabljena),
     * torej R_c = 29 dB.
     *
     * @return void
     */
    public function testPrispevekElementovPoPovrsini(): void
    {
        $fasada = $this->fasada(
            ['povrsina' => 3.0, 'stevilo' => 2],
            ['povrsina' => 5.0, 'stevilo' => 1]
        );

        $tau = 6 / 11 * pow(10, -50 / 10) + 5 / 11 * pow(10, -29 / 10);
        $this->assertEqualsWithDelta(-10 * log10($tau), $fasada->Rw, 0.0001);
    }
}
