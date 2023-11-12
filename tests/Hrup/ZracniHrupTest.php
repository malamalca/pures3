<?php
declare(strict_types=1);

use App\Calc\Hrup\ZracniHrup\ZracniHrupPoenostavljen;
use App\Core\App;
use PHPUnit\Framework\TestCase;

class ZracniHrupTest extends TestCase
{
    public function testPrimer1Standard12354_1(): void
    {
        $konstrukcijeLib = json_decode(<<<EOT
        [
            {
                "id": "ZH.1",
                "naziv": "Zvok v zraku - ločilna - beton 200mm",
                "povrsinskaMasa": 460,
                "Rw": 57
            },
            {
                "id": "ZH.2",
                "naziv": "Zvok v zraku - fasada - bloki kalcijev silikat 100mm",
                "povrsinskaMasa": 175,
                "Rw": 42
            },
            {
                "id": "ZH.3",
                "naziv": "Zvok v zraku - notranja stena - mavčni bloki 70mm",
                "povrsinskaMasa": 67,
                "Rw": 33
            },
            {
                "id": "ZH.4",
                "naziv": "Zvok v zraku - strop - beton 100mm",
                "povrsinskaMasa": 230,
                "Rw": 46
            },
            {
                "id": "ZH.5",
                "naziv": "Zvok v zraku - talna konstrukcija - beton 100mm/zaključni sloj 30mm",
                "povrsinskaMasa": 287,
                "Rw": 49
            }
        ]
        EOT);

        $locilnaKonstrukcijaConfig = json_decode(<<<EOT
        {
            "id": "S.1",
            "naziv": "Testna konstrukcija",
            "model": "poenostavljen",
            "locilniElement": {
                "idKonstrukcije": "ZH.1",
                "povrsina": "4.5*2.55"
            },
            
            "stranskiElementi": [
                {
                    "idKonstrukcije": "ZH.2",
                    "povrsina": "4.36*2.55",
                    "vrstaSpoja": "togiT",
                    "dolzinaSpoja": 2.55,
                    "pozicijeElementov": {
                        "locilni": 2,
                        "izvorni": 1,
                        "oddajni": 3
                    }
                },
                {
                    "idKonstrukcije": "ZH.3",
                    "vrstaSpoja": "krizniElasticni",
                    "povrsina": "4.36*2.55",
                    "dolzinaSpoja": 2.55,
                    "pozicijeElementov": {
                        "locilni": 2,
                        "izvorni": 1,
                        "oddajni": 3
                    }
                },
                {
                    "idKonstrukcije": "ZH.4",
                    "vrstaSpoja": "togiKrizni",
                    "povrsina": "4.36*4.5",
                    "dolzinaSpoja": 4.5,
                    "pozicijeElementov": {
                        "locilni": 2,
                        "izvorni": 1,
                        "oddajni": 3
                    }
                },
                {
                    "idKonstrukcije": "ZH.5",
                    "vrstaSpoja": "togiKrizni",
                    "povrsina": "4.36*4.5",
                    "dolzinaSpoja": 4.5,
                    "pozicijeElementov": {
                        "locilni": 2,
                        "izvorni": 1,
                        "oddajni": 3
                    }
                }
            ]
        }
        EOT);

        $hrup = new ZracniHrupPoenostavljen($konstrukcijeLib, $locilnaKonstrukcijaConfig);
        $hrup->analiza();

        // fasada
        $this->assertEquals(6.7, round($hrup->stranskiElementi[0]->K_Df, 1));
        $this->assertEquals(6.7, round($hrup->stranskiElementi[0]->K_Fd, 1));
        $this->assertEquals(12.6, round($hrup->stranskiElementi[0]->K_Ff, 1));
        $this->assertEquals(62.7, round($hrup->stranskiElementi[0]->R_Df, 1));
        $this->assertEquals(61.2, round($hrup->stranskiElementi[0]->R_Ff, 1));
        $this->assertEquals(62.7, round($hrup->stranskiElementi[0]->R_Fd, 1));

        // stena
        $this->assertEquals(15.7, round($hrup->stranskiElementi[1]->K_Df, 1));
        $this->assertEquals(15.7, round($hrup->stranskiElementi[1]->K_Fd, 1));
        $this->assertEquals(33.5, round($hrup->stranskiElementi[1]->K_Ff, 1));
        $this->assertEquals(67.2, round($hrup->stranskiElementi[1]->R_Df, 1));
        $this->assertEquals(73.1, round($hrup->stranskiElementi[1]->R_Ff, 1));
        $this->assertEquals(67.2, round($hrup->stranskiElementi[1]->R_Fd, 1));

        // strop
        $this->assertEquals(9.2, round($hrup->stranskiElementi[2]->K_Df, 1));
        $this->assertEquals(9.2, round($hrup->stranskiElementi[2]->K_Fd, 1));
        $this->assertEquals(14.4, round($hrup->stranskiElementi[2]->K_Ff, 1));
        $this->assertEquals(64.8, round($hrup->stranskiElementi[2]->R_Df, 1));
        $this->assertEquals(64.4, round($hrup->stranskiElementi[2]->R_Ff, 1));
        $this->assertEquals(64.8, round($hrup->stranskiElementi[2]->R_Fd, 1));

        // tla
        $this->assertEquals(8.9, round($hrup->stranskiElementi[3]->K_Df, 1));
        $this->assertEquals(8.9, round($hrup->stranskiElementi[3]->K_Fd, 1));
        $this->assertEquals(12.4, round($hrup->stranskiElementi[3]->K_Ff, 1));
        $this->assertEquals(66.0, round($hrup->stranskiElementi[3]->R_Df, 1));
        $this->assertEquals(65.5, round($hrup->stranskiElementi[3]->R_Ff, 1));
        $this->assertEquals(66.0, round($hrup->stranskiElementi[3]->R_Fd, 1));

        $this->assertEquals(52, round($hrup->Rw, 0));
    }

    public function testPrimer2Standard12354_1(): void
    {
        $konstrukcijeLib = json_decode(<<<EOT
        [
            {
                "id": "ZH.1",
                "naziv": "Zvok v zraku - ločilna - beton 200mm",
                "povrsinskaMasa": 460,
                "Rw": 57
            },
            {
                "id": "ZH.2",
                "naziv": "Zvok v zraku - fasada - bloki kalcijev silikat 100mm",
                "povrsinskaMasa": 175,
                "Rw": 42
            },
            {
                "id": "ZH.3",
                "naziv": "Zvok v zraku - notranja stena - mavčni bloki 70mm",
                "povrsinskaMasa": 67,
                "Rw": 33
            },
            {
                "id": "ZH.4",
                "naziv": "Zvok v zraku - strop - beton 100mm",
                "povrsinskaMasa": 230,
                "Rw": 46
            },
            {
                "id": "ZH.5",
                "naziv": "Zvok v zraku - talna konstrukcija - beton 100mm/zaključni sloj 30mm",
                "povrsinskaMasa": 287,
                "Rw": 49,
                "dodatniSloji": [
                    {
                        "id": "estrih",
                        "vrsta": "elasticen",
                        "naziv": "estrih",
                        "povrsinskaMasa": 80,
                        "dinamicnaTogost": 8,
                        "dR": 14
                    }
                ]
            }
        ]
        EOT);

        $locilnaKonstrukcijaConfig = json_decode(<<<EOT
        {
            "id": "S.1",
            "naziv": "Testna konstrukcija",
            "model": "poenostavljen",
            "locilniElement": {
                "idKonstrukcije": "ZH.1",
                "povrsina": "4.5*2.55"
            },
            
            "stranskiElementi": [
                {
                    "idKonstrukcije": "ZH.2",
                    "povrsina": "4.36*2.55",
                    "vrstaSpoja": "togiT",
                    "dolzinaSpoja": 2.55,
                    "pozicijeElementov": {
                        "locilni": 2,
                        "izvorni": 1,
                        "oddajni": 3
                    }
                },
                {
                    "idKonstrukcije": "ZH.3",
                    "vrstaSpoja": "krizniElasticni",
                    "povrsina": "4.36*2.55",
                    "dolzinaSpoja": 2.55,
                    "pozicijeElementov": {
                        "locilni": 2,
                        "izvorni": 1,
                        "oddajni": 3
                    }
                },
                {
                    "idKonstrukcije": "ZH.4",
                    "vrstaSpoja": "togiKrizni",
                    "povrsina": "4.36*4.5",
                    "dolzinaSpoja": 4.5,
                    "pozicijeElementov": {
                        "locilni": 2,
                        "izvorni": 1,
                        "oddajni": 3
                    }
                },
                {
                    "idKonstrukcije": "ZH.5",
                    "idDodatnegaSloja": "estrih",
                    "vrstaSpoja": "togiKrizni",
                    "povrsina": "4.36*4.5",
                    "dolzinaSpoja": 4.5,
                    "pozicijeElementov": {
                        "locilni": 2,
                        "izvorni": 1,
                        "oddajni": 3
                    }
                }
            ]
        }
        EOT);

        $hrup = new ZracniHrupPoenostavljen($konstrukcijeLib, $locilnaKonstrukcijaConfig);
        $hrup->analiza();

        // fasada
        $this->assertEquals(6.7, round($hrup->stranskiElementi[0]->K_Df, 1));
        $this->assertEquals(6.7, round($hrup->stranskiElementi[0]->K_Fd, 1));
        $this->assertEquals(12.6, round($hrup->stranskiElementi[0]->K_Ff, 1));
        $this->assertEquals(62.7, round($hrup->stranskiElementi[0]->R_Df, 1));
        $this->assertEquals(61.2, round($hrup->stranskiElementi[0]->R_Ff, 1));
        $this->assertEquals(62.7, round($hrup->stranskiElementi[0]->R_Fd, 1));

        // stena
        $this->assertEquals(15.7, round($hrup->stranskiElementi[1]->K_Df, 1));
        $this->assertEquals(15.7, round($hrup->stranskiElementi[1]->K_Fd, 1));
        $this->assertEquals(33.5, round($hrup->stranskiElementi[1]->K_Ff, 1));
        $this->assertEquals(67.2, round($hrup->stranskiElementi[1]->R_Df, 1));
        $this->assertEquals(73.1, round($hrup->stranskiElementi[1]->R_Ff, 1));
        $this->assertEquals(67.2, round($hrup->stranskiElementi[1]->R_Fd, 1));

        // strop
        $this->assertEquals(9.2, round($hrup->stranskiElementi[2]->K_Df, 1));
        $this->assertEquals(9.2, round($hrup->stranskiElementi[2]->K_Fd, 1));
        $this->assertEquals(14.4, round($hrup->stranskiElementi[2]->K_Ff, 1));
        $this->assertEquals(64.8, round($hrup->stranskiElementi[2]->R_Df, 1));
        $this->assertEquals(64.4, round($hrup->stranskiElementi[2]->R_Ff, 1));
        $this->assertEquals(64.8, round($hrup->stranskiElementi[2]->R_Fd, 1));

        // tla
        $this->assertEquals(8.9, round($hrup->stranskiElementi[3]->K_Df, 1));
        $this->assertEquals(8.9, round($hrup->stranskiElementi[3]->K_Fd, 1));
        $this->assertEquals(12.4, round($hrup->stranskiElementi[3]->K_Ff, 1));
        $this->assertEquals(80.0, round($hrup->stranskiElementi[3]->R_Df, 1));
        $this->assertEquals(86.5, round($hrup->stranskiElementi[3]->R_Ff, 1));
        $this->assertEquals(80.0, round($hrup->stranskiElementi[3]->R_Fd, 1));

        $this->assertEquals(53, round($hrup->Rw, 0));
    }
}