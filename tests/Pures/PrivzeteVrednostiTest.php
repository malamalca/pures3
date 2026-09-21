<?php
declare(strict_types=1);

namespace App\Test\Pures;

use App\Calc\GF\TSS\FotonapetostniSistemi\FotonapetostniSistem;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire\VrstaSSE;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\SolarniPaneli;
use App\Calc\GF\TSS\OHTSistemi\Sistemi\TSV;
use App\Calc\GF\TSS\Razsvetljava\Razsvetljava;
use PHPUnit\Framework\TestCase;

/**
 * Privzete vrednosti in obvezni vnosi, ki jih shema ne more preveriti sama.
 */
class PrivzeteVrednostiTest extends TestCase
{
    /**
     * Privzeti tip SSE mora obstajati v VrstaSSE - enak je referenčnemu SSE po TSG.
     *
     * @return void
     */
    public function testSolarniPaneliBrezTipaUporabijoZastekljenSSE(): void
    {
        $paneli = new SolarniPaneli((object)['id' => 'SSE', 'povrsina' => 4]);

        $this->assertSame(VrstaSSE::Zastekljen, $paneli->tip);
    }

    /**
     * @return void
     */
    public function testSolarniPaneliUpostevajoVpisanTip(): void
    {
        $paneli = new SolarniPaneli((object)['id' => 'SSE', 'povrsina' => 4, 'tip' => 'vakuumskiCevniAbsorber']);

        $this->assertSame(VrstaSSE::VakuumskiSCevnimAbosrberjem, $paneli->tip);
    }

    /**
     * Manjkajoč režim TSV mora javiti razumljivo napako in ne TypeError.
     *
     * @return void
     */
    public function testTSVBrezRezimaJavijaNapako(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ni vpisanega temperaturnega režima sistema TSV.');

        new TSV((object)['id' => 'TSV', 'generatorji' => ['G']]);
    }

    /**
     * @return void
     */
    public function testTSVSprejmeVpisanRezim(): void
    {
        $tsv = new TSV((object)['id' => 'TSV', 'rezim' => '55/45', 'generatorji' => ['G']]);

        $this->assertSame('55/45', $tsv->rezim->value);
    }

    /**
     * Vpisana energija varnostne razsvetljave se mora upoštevati v letni dovedeni energiji.
     *
     * @return void
     */
    public function testVarnostnaRazsvetljavaSeUpostevaVIzracunu(): void
    {
        $cona = (object)['id' => 'C1', 'ogrevanaPovrsina' => 100, 'klasifikacija' => '11100'];
        $okolje = (object)[];

        $config = [
            'id' => 'R',
            'idCone' => 'C1',
            'faktorDnevneSvetlobe' => 2,
            'mocSvetilk' => 6,
            'letnoUrPodnevi' => 1820,
            'letnoUrPonoci' => 1680,
        ];

        $brezVarnostne = new Razsvetljava((object)$config);
        $brezVarnostne->analiza([], $cona, $okolje);

        $zVarnostno = new Razsvetljava((object)($config + [
            'varnostna' => (object)['energijaZaPolnjenje' => 1.0, 'energijaZaDelovanje' => 2.0],
        ]));
        $zVarnostno->analiza([], $cona, $okolje);

        $razlika = $zVarnostno->skupnaPotrebnaEnergija - $brezVarnostne->skupnaPotrebnaEnergija;

        // 3 kWh/(m²a) x 100 m² ogrevane površine; mesečna porazdelitev z utežnimi faktorji da manjše odstopanje
        $this->assertGreaterThan(0, $razlika);
        $this->assertEqualsWithDelta(300, $razlika, 3);
    }

    /**
     * Vpisani koeficient moči mora vplivati na proizvedeno energijo.
     *
     * @return void
     */
    public function testFotovoltaikaUpostevaVpisanKoeficientMoci(): void
    {
        $okolje = (object)[
            'obsevanje' => [
                (object)['orientacija' => 'J', 'naklon' => 30, 'obsevanje' => array_fill(0, 12, 4000)],
            ],
        ];

        $config = [
            'id' => 'PV',
            'orientacija' => 'J',
            'naklon' => 30,
            'povrsina' => 10,
            'vrsta' => 'polikristalne',
            'oddajaVOmrezje' => true,
        ];

        $potrebnaEnergija = array_fill(0, 12, 500.0);

        $privzeti = new FotonapetostniSistem((object)$config);
        $privzeti->analiza($potrebnaEnergija, $okolje);

        $vpisani = new FotonapetostniSistem((object)($config + ['koeficientMoci' => 0.2]));
        $vpisani->analiza($potrebnaEnergija, $okolje);

        $this->assertSame(0.2, $vpisani->koeficientMoci);
        $this->assertNotEqualsWithDelta(
            array_sum($privzeti->proizvedenaElektricnaEnergija),
            array_sum($vpisani->proizvedenaElektricnaEnergija),
            0.001
        );
    }
}
