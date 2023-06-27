<?php
declare(strict_types=1);

use App\Calc\GF\Cone\Cona;
use App\Core\App;
use PHPUnit\Framework\TestCase;

class IzracunConeTest extends TestCase
{
    /**
     * @inheritDoc
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function testValidacijaIzracunaCone(): void
    {
        /** @var \stdClass $okolje */
        $okolje = json_decode(file_get_contents(PROJECTS . 'TestniProjekt' . DS . 'izracuni' . DS . 'okolje.json'));

        /** @var array $netransparentneKonstrukcije */
        $netransparentneKonstrukcije = json_decode(file_get_contents(PROJECTS . 'TestniProjekt' . DS . 'izracuni' . DS . 'konstrukcije' . DS . 'netransparentne.json'));

        /** @var array $transparentneKonstrukcije */
        $transparentneKonstrukcije = json_decode(file_get_contents(PROJECTS . 'TestniProjekt' . DS . 'izracuni' . DS . 'konstrukcije' . DS . 'transparentne.json'));

        /** @var array $coneIn */
        $coneIn = json_decode(file_get_contents(PROJECTS . 'TestniProjekt' . DS . 'podatki' . DS . 'cone.json'));

        $cona = new Cona($coneIn[0]);
        $cona->analiza($okolje, $netransparentneKonstrukcije, $transparentneKonstrukcije);

        // transmisijske izgube ogrevanja
        $roundedResult = array_map(fn($el) => round($el, 2), $cona->transIzgubeOgrevanje);
        $expected = [1963.44, 1615.15, 1437.74, 1108.74, 740.81, 481.82, 335.93, 335.93, 638.55, 999.65, 1476.15, 1875.82];
        $this->assertEquals($expected, $roundedResult);

        // prezračevalne izgube ogrevanja
        $roundedResult = array_map(fn($el) => round($el, 2), $cona->prezracevalneIzgubeOgrevanje);
        $expected = [127.87, 104.49, 91.33, 64.82, 36.53, 17.68, 6.09, 6.09, 29.46, 60.89, 94.28, 121.78];
        $this->assertEquals($expected, $roundedResult);

        // dobitki notranjih bremen
        $roundedResult = array_map(fn($el) => round($el, 2), $cona->notranjiViriOgrevanje);
        $expected = [488.06, 440.83, 488.06, 472.32, 488.06, 472.32, 488.06, 488.06, 472.32, 488.06, 472.32, 488.06];
        $this->assertEquals($expected, $roundedResult);

        // dobitki sončnega obsevanja
        // vrednosti se razlikujejo od Excela V150 za ca 0.04
        // razlog je v tem, ker excel upošteva in računa negativne solarne dobitke, kar ni ok
        $roundedResult = array_map(fn($el) => round($el, 2), $cona->solarniDobitkiOgrevanje);
        $expected = [407.44, 571.91, 803.99, 928.99, 1012.98, 884.11, 933.36, 933.82, 789.15, 601.30, 358.72, 308.1];
        $this->assertEquals($expected, $roundedResult);

        // faktor izkoristljivosti dobitkov
        $roundedResult = array_map(fn($el) => round($el ?? 0, 3), $cona->ucinekDobitkov);
        $expected = [0.988, 0.961, 0.882, 0.741, 0.505, 0.0, 0.0, 0.0, 0.515, 0.809, 0.973, 0.991];
        $this->assertEquals($expected, $roundedResult);

        // skupna energija v času ogrevanja
        $roundedResult = array_map(fn($el) => round($el ?? 0, 2), $cona->energijaOgrevanje);
        $expected = [1206.66, 746.33, 390.09, 135.72, 19.22, 0.0, 0.0, 0.0, 17.9, 179.48, 761.61, 1208.74];
        $this->assertEquals($expected, $roundedResult);

        // transmisijske izgube hlajenja
        $roundedResult = array_map(fn($el) => round($el, 2), $cona->transIzgubeHlajenje);
        $expected = [2424.81, 2031.87, 1899.10, 1555.22, 1202.18, 928.30, 797.29, 797.29, 1085.03, 1461.02, 1922.63, 2337.19];
        $this->assertEquals($expected, $roundedResult);

        // prezračevalne izgube hlajenja
        $roundedResult = array_map(fn($el) => round($el, 2), $cona->prezracevalneIzgubeHlajenje);
        $expected = [1357.63, 1135.41, 1055.93, 827.23, 603.39, 437.94, 351.98, 351.98, 535.27, 804.52, 1070.53, 1307.34];
        $this->assertEquals($expected, $roundedResult);

        // dobitki notranjih bremen
        $roundedResult = array_map(fn($el) => round($el, 2), $cona->notranjiViriHlajenje);
        $expected = [488.06, 440.83, 488.06, 472.32, 488.06, 472.32, 488.06, 488.06, 472.32, 488.06, 472.32, 488.06];
        $this->assertEquals($expected, $roundedResult);

        // dobitki sončnega obsevanja
        // vrednosti se razlikujejo od Excela V150 za ca 0.04
        // razlog je v tem, ker excel upošteva in računa negativne solarne dobitke, kar ni ok
        $roundedResult = array_map(fn($el) => round($el, 2), $cona->solarniDobitkiHlajenje);
        $expected = [61.13, 83.7, 108.46, 117.94, 122.15, 117.21, 122.1, 123.82, 110.42, 84.96, 51.52, 45.27];
        $this->assertEquals($expected, $roundedResult);

        // faktor izkoristljivosti dobitkov
        $roundedResult = array_map(fn($el) => round($el ?? 0, 3), $cona->ucinekPonorov);
        $expected = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.503, 0.504, 0.0, 0.0, 0.0, 0.0];
        $this->assertEquals($expected, $roundedResult);

        // faktor izkoristljivosti dobitkov
        $roundedResult = array_map(fn($el) => round($el ?? 0, 2), $cona->energijaHlajenje);
        $expected = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 32.42, 32.75, 0.0, 0.0, 0.0, 0.0];
        $this->assertEquals($expected, $roundedResult);

        // tsv
        $roundedResult = array_map(fn($el) => round($el ?? 0, 2), $cona->energijaTSV);
        $expected = [114.25, 103.20, 114.25, 110.57, 114.25, 110.57, 114.25, 114.25, 110.57, 114.25, 110.57, 114.25];
        $this->assertEquals($expected, $roundedResult);

        // razsvetljava
        $roundedResult = array_map(fn($el) => round($el ?? 0, 2), $cona->energijaRazsvetljava);
        $expected = [131.84, 104.79, 99.14, 87.78, 87.54, 74.51, 83.32, 91.76, 95.95, 114.97, 123.51, 142.39];
        $this->assertEquals($expected, $roundedResult);

        // končne vrednosti
        $this->assertEquals(4665.76, round($cona->skupnaEnergijaOgrevanje, 2));
        $this->assertEquals(65.17, round($cona->skupnaEnergijaHlajenje, 2));
        $this->assertEquals(1345.25, round($cona->skupnaEnergijaTSV, 2));
        $this->assertEquals(1237.50, round($cona->skupnaEnergijaRazsvetljava, 2));

        $this->assertEquals(143.88, round($cona->specTransmisijskeIzgube, 2));
        $this->assertEquals(8.18, round($cona->specVentilacijskeIzgube, 2));
        $this->assertEquals(0.212, round($cona->specKoeficientTransmisijskihIzgub, 3));
        $this->assertEquals(29.16, round($cona->specLetnaToplota, 2));
        $this->assertEquals(0.41, round($cona->specLetniHlad, 2));
    }
}
