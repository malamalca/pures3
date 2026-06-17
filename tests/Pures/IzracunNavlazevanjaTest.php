<?php
declare(strict_types=1);

namespace App\Test\Pures;

use PHPUnit\Framework\TestCase;

final class IzracunNavlazevanjaTest extends TestCase
{
    public function testValidacijaTSG(): void
    {
        $konstrukcija = null;

        //$cona = new \stdClass();
        $cona = new \App\Calc\GF\Cone\Cona();
        $cona->ogrevanaPovrsina = 1632;
        $cona->notranjaTOgrevanje = 22;
        $cona->notranjaTHlajenje = 26;
        $cona->prezracevanje = new \stdClass();
        $cona->prezracevanje->volumenDovedenegaZraka = new \stdClass();
        $cona->prezracevanje->volumenDovedenegaZraka->ogrevanje = 3345;
        $cona->prezracevanje->volumenDovedenegaZraka->hlajenje = 3345;

        $cona->uravnavanjeVlage = new \stdClass();
        $cona->uravnavanjeVlage->faktorUporabe = 0.3;
        $cona->uravnavanjeVlage->vlaznostZrakaNavlazevanje = 30;
        $cona->uravnavanjeVlage->viriVodnePare = 3.6;
        // pravilno ime lastnosti je minNotranjaVlaznostOgrevanje (koda bere to, ne "minNotranjaVlaznost")
        $cona->uravnavanjeVlage->minNotranjaVlaznostOgrevanje = 4.960;

        $okolje = new \stdClass();
        $okolje->zunanjaT = [-1, 1, 6, 10, 15, 18, 20, 19, 15, 10, 4, 1];
        $okolje->zunanjaVlaga = [82, 77, 72, 71, 73, 72, 75, 76, 80, 82, 84, 85];
        $okolje->absVlaznost = [3.98, 4.16, 5.96, 7.65, 11.86, 14.83, 15.24, 14.79, 10.47, 8.14, 5.85, 4.55];

        //$cona = new \App\Calc\GF\Cone\Cona($cona);
        $cona->izracunNavlazevanje($okolje, ['details' => true]);
        //\App\Lib\CalcCone::izracunNavlazevanje($cona, $okolje, ['details' => true]);

        $expected_X_eam = [3.98, 4.16, 5.96, 7.65, 11.86, 14.83, 15.24, 14.79, 10.47, 8.14, 5.85, 4.55];
        $roundedResult = array_map(fn($el) => round($el, 2), $cona->uravnavanjeVlage->absZunanjaVlaznost);
        $this->assertEquals($expected_X_eam, $roundedResult);

        $expected_G_h2o = [1311, 1184, 1311, 1269, 1311, 1269, 1311, 1311, 1269, 1311, 1269, 1311];
        $roundedResult = array_map(fn($el) => (int)round($el, 0), $cona->uravnavanjeVlage->mesecnaKolicinaVodnePare);
        $this->assertEquals($expected_G_h2o, $roundedResult);

        // m_h2o,HU,m (potrebna mesečna količina vode za navlaževanje); vhodni Xe je zaokrožen na 2 decimalki,
        // zato dovolimo majhno odstopanje od vrednosti v tabeli 8.12.1.
        $expected_m_h2o_HU_m = [3064, 2248, -3122, -8097, -21452, -29708, -31974, -30576, -16591, -9882, -2674, 1265];
        $this->assertEqualsWithDelta(
            $expected_m_h2o_HU_m,
            array_map(fn($el) => round($el, 0), $cona->uravnavanjeVlage->potrebnaMesecnaKolicinaVodeOgrevanje),
            20
        );
    }

    /**
     * TSG-1-004:2022, tabela 8.12.1 - potrebna toplota za navlaževanje QHU,nd,m in QHU,nd,an.
     * Primer predpostavlja, da entalpijski prenosnik ni vgrajen (ηHU = 0), kar potrjuje
     * QHU,nd,m(jan) = (m_h2o,HU,m - G_h2o,m) * hwe/3600 ≈ 1201 kWh.
     * QHU,nd,m je nič, kadar je (m_h2o,HU,m - G_h2o,m) < 0; QHU,nd,an ≈ 1929 kWh/an.
     */
    public function testValidacijaTSG_QHU(): void
    {
        $cona = new \App\Calc\GF\Cone\Cona();
        $cona->ogrevanaPovrsina = 1632;
        $cona->notranjaTOgrevanje = 22;
        $cona->notranjaTHlajenje = 26;
        $cona->prezracevanje = new \stdClass();
        $cona->prezracevanje->volumenDovedenegaZraka = new \stdClass();
        $cona->prezracevanje->volumenDovedenegaZraka->ogrevanje = 3345;
        $cona->prezracevanje->volumenDovedenegaZraka->hlajenje = 3345;

        $cona->uravnavanjeVlage = new \stdClass();
        $cona->uravnavanjeVlage->faktorUporabe = 0.3;
        $cona->uravnavanjeVlage->vlaznostZrakaNavlazevanje = 30;
        $cona->uravnavanjeVlage->viriVodnePare = 3.6;
        $cona->uravnavanjeVlage->minNotranjaVlaznostOgrevanje = 4.960;
        // primer 8.12.1 ne predvideva entalpijskega prenosnika -> ηHU = 0
        $cona->uravnavanjeVlage->ucinkovitostPrenosnika = 0;

        $okolje = new \stdClass();
        $okolje->zunanjaT = [-1, 1, 6, 10, 15, 18, 20, 19, 15, 10, 4, 1];
        $okolje->zunanjaVlaga = [82, 77, 72, 71, 73, 72, 75, 76, 80, 82, 84, 85];
        $okolje->absVlaznost = [3.98, 4.16, 5.96, 7.65, 11.86, 14.83, 15.24, 14.79, 10.47, 8.14, 5.85, 4.55];

        $cona->izracunNavlazevanje($okolje, ['details' => true]);

        // QHU,nd,m (kWh/m) - tabela 8.12.1
        $expected_QHU_m = [1201, 729, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $this->assertEqualsWithDelta(
            $expected_QHU_m,
            array_map(fn($el) => round($el, 0), $cona->energijaNavlazevanje),
            15
        );

        // QHU,nd,an (kWh/an) - tabela 8.12.1
        $this->assertEqualsWithDelta(1929, $cona->skupnaEnergijaNavlazevanje, 15);
    }

    /**
     * TSG-1-004:2022, tabela 8.12.2 - potrebna toplota za razvlaževanje QDHU,nd,m in QDHU,nd,an.
     * Primer poslovne stavbe (qi = 25 °C, ji = 60 %, največja dovoljena absolutna vlažnost Xi,a,min = 12 g/kg).
     * QDHU,nd,m = (mH2O,HU,m + G_h2o,m) * hwe/3600, omejeno na ≥ 0; QDHU,nd,an ≈ 21932 kWh/an.
     */
    public function testValidacijaTSG_QDHU(): void
    {
        $cona = new \App\Calc\GF\Cone\Cona();
        $cona->ogrevanaPovrsina = 1632;
        $cona->notranjaTOgrevanje = 22;
        $cona->notranjaTHlajenje = 25;
        $cona->prezracevanje = new \stdClass();
        $cona->prezracevanje->volumenDovedenegaZraka = new \stdClass();
        $cona->prezracevanje->volumenDovedenegaZraka->ogrevanje = 3345;
        $cona->prezracevanje->volumenDovedenegaZraka->hlajenje = 3345;

        $cona->uravnavanjeVlage = new \stdClass();
        $cona->uravnavanjeVlage->faktorUporabe = 0.3;
        $cona->uravnavanjeVlage->vlaznostZrakaRazvlazevanje = 60;
        $cona->uravnavanjeVlage->viriVodnePare = 3.6;
        // največja dovoljena absolutna vlažnost zraka pri hlajenju (Xi,a,min v tabeli 8.12.2) = 12 g/kg
        $cona->uravnavanjeVlage->minNotranjaVlaznostHlajenje = 12.00;

        $okolje = new \stdClass();
        $okolje->zunanjaT = [-1, 1, 6, 10, 15, 18, 20, 19, 15, 10, 4, 1];
        $okolje->zunanjaVlaga = [82, 77, 72, 71, 73, 72, 75, 76, 80, 82, 84, 85];
        $okolje->absVlaznost = [3.98, 4.16, 5.96, 7.65, 11.86, 14.83, 15.24, 14.79, 10.47, 8.14, 5.85, 4.55];

        $cona->izracunNavlazevanje($okolje, ['details' => true]);

        // QDHU,nd,m (kWh/m) - tabela 8.12.2 (le poletni meseci > 0)
        $expected_QDHU_m = [0, 0, 0, 0, 591, 6701, 7798, 6841, 0, 0, 0, 0];
        $this->assertEqualsWithDelta(
            $expected_QDHU_m,
            array_map(fn($el) => round($el, 0), $cona->energijaRazvlazevanje),
            20
        );

        // QDHU,nd,an (kWh/an) - tabela 8.12.2
        $this->assertEqualsWithDelta(21932, $cona->skupnaEnergijaRazvlazevanje, 30);
    }
}
