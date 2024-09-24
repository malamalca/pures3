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
        $cona->uravnavanjeVlage->minNotranjaVlaznost = 4.960;

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

        // TODO:
        //$expected_m_h2o_HU_m = [3064, 2248, -3122, -8097, -21452, -29708, -31974, -30576, -16591, -9882, -2674, 1265];
        //$roundedResult = array_map(fn($el) => (int)round($el, 0), $cona->uravnavanjeVlage->potrebnaMesecnaKolicinaVodeOgrevanje);
        //$this->assertEquals($expected_m_h2o_HU_m, $roundedResult);
    }
}
