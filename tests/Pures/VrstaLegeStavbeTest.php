<?php
declare(strict_types=1);

namespace App\Test\Pures;

use App\Calc\GF\Cone\Izbire\VrstaIzpostavljenostiFasad;
use App\Calc\GF\Cone\Izbire\VrstaLegeStavbe;
use PHPUnit\Framework\TestCase;

/**
 * Koeficienti vpliva vetra po tabeli 8.8, preverjeni z validiranim modelom
 * resources/TestniProjekt170.xlsm (list C1, celice P224:R226).
 *
 * Razred 1: odprto podeželje, visoke stavbe v mestih          e1 = 0,10  e2 = 0,03
 * Razred 2: podeželje z drevesi, obkrožene stavbe, predmestja e1 = 0,07  e2 = 0,02
 * Razred 3: stavbe v mestih povprečne višine, stavbe v gozdu  e1 = 0,04  e2 = 0,01
 */
class VrstaLegeStavbeTest extends TestCase
{
    /**
     * @dataProvider primeriKoeficientov
     * @param string $lega Vrsta lege stavbe
     * @param float $e1 Koeficient pri eni izpostavljeni fasadi
     * @param float $e2 Koeficient pri več izpostavljenih fasadah
     * @return void
     */
    public function testKoeficientVplivaVetra(string $lega, float $e1, float $e2): void
    {
        $lega = VrstaLegeStavbe::from($lega);

        // vrednosti niza določata vrsti izpostavljenosti, ne imeni primerov enuma
        $enaFasada = VrstaIzpostavljenostiFasad::from('izpostavljenaEnaFasada');
        $vecFasad = VrstaIzpostavljenostiFasad::from('izpostavljenihVecFasad');

        $this->assertSame($e1, $lega->koeficientVplivaVetra($enaFasada));
        $this->assertSame($e2, $lega->koeficientVplivaVetra($vecFasad));
    }

    /**
     * @dataProvider primeriSifer
     * @param string $lega Vrsta lege stavbe
     * @param int $sifra Šifra razreda za EI XML
     * @return void
     */
    public function testSifraEI(string $lega, int $sifra): void
    {
        $this->assertSame($sifra, VrstaLegeStavbe::from($lega)->sifraEI());
    }

    /**
     * Faktor vetra f: ena izpostavljena fasada 15, več izpostavljenih fasad 20.
     *
     * @return void
     */
    public function testFaktorVetra(): void
    {
        $this->assertSame(15, VrstaIzpostavljenostiFasad::from('izpostavljenaEnaFasada')->faktorVetra());
        $this->assertSame(20, VrstaIzpostavljenostiFasad::from('izpostavljenihVecFasad')->faktorVetra());
    }

    /**
     * @return array
     */
    public static function primeriKoeficientov(): array
    {
        return [
            ['naPodezelju', 0.1, 0.03],
            ['visokaStavbaVMestu', 0.1, 0.03],
            ['naPodezeljuMedDrevesi', 0.07, 0.02],
            ['obkrozenaStavbaVMestu', 0.07, 0.02],
            ['stavbaVPredmestju', 0.07, 0.02],
            ['povprecnaStavbaVMestu', 0.04, 0.01],
            ['stavbaVGozdu', 0.04, 0.01],
        ];
    }

    /**
     * @return array
     */
    public static function primeriSifer(): array
    {
        return [
            ['naPodezelju', 1],
            ['visokaStavbaVMestu', 1],
            ['naPodezeljuMedDrevesi', 2],
            ['obkrozenaStavbaVMestu', 2],
            ['stavbaVPredmestju', 2],
            ['povprecnaStavbaVMestu', 3],
            ['stavbaVGozdu', 3],
        ];
    }
}
