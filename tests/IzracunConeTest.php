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
        $okolje = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'okolje.json'));

        /** @var array $netransparentneKonstrukcije */
        $netransparentneKonstrukcije = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'konstrukcije' . DS . 'netransparentne.json'));

        /** @var array $transparentneKonstrukcije */
        $transparentneKonstrukcije = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'konstrukcije' . DS . 'transparentne.json'));

        /** @var array $coneIn */
        $coneIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'podatki' . DS . 'cone.json'));

        $konstrukcije = new \stdClass();
        $konstrukcije->transparentne = $transparentneKonstrukcije;
        $konstrukcije->netransparentne = $netransparentneKonstrukcije;

        $cona = new Cona($konstrukcije, $coneIn[0]);
        $cona->analiza($okolje);

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
        $expected = [317.05, 490.27, 713.61, 841.52, 922.59, 796.64, 842.98, 843.43, 701.68, 510.91, 271.25, 217.71];
        $this->assertEquals($expected, $roundedResult);

        // faktor izkoristljivosti dobitkov
        $roundedResult = array_map(fn($el) => round($el ?? 0, 3), $cona->ucinekDobitkov);
        $expected = [0.992, 0.971, 0.903, 0.771, 0.534, 0.0, 0.0, 0.0, 0.549, 0.844, 0.982, 0.994];
        $this->assertEquals($expected, $roundedResult);

        // skupna energija v času ogrevanja
        $roundedResult = array_map(fn($el) => round($el ?? 0, 2), $cona->energijaOgrevanje);
        $expected = [1292.64, 815.56, 443.82, 160.83, 24.01, 0.0, 0.0, 0.0, 23.09, 217.84, 840.08, 1295.84];
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
        $roundedResult = array_map(fn($el) => round($el, 2), $cona->solarniDobitkiHlajenje);
        $expected = [8.72, 53.84, 83.48, 98.24, 100.08, 92.89, 99.24, 104.62, 88.25, 45.49, -5.01, -17.37];
        $this->assertEquals($expected, $roundedResult);

        // faktor izkoristljivosti dobitkov
        $roundedResult = array_map(fn($el) => round($el ?? 0, 3), $cona->ucinekPonorov);
        $expected = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.487, 0.490, 0.0, 0.0, 0.0, 0.0];
        $this->assertEquals($expected, $roundedResult);

        // faktor izkoristljivosti dobitkov
        $roundedResult = array_map(fn($el) => round($el ?? 0, 2), $cona->energijaHlajenje);
        $expected = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 28.15, 29.12, 0.0, 0.0, 0.0, 0.0];
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
        $this->assertEquals(5113.69, round($cona->skupnaEnergijaOgrevanje, 2));
        $this->assertEquals(57.27, round($cona->skupnaEnergijaHlajenje, 2));
        $this->assertEquals(1345.25, round($cona->skupnaEnergijaTSV, 2));
        $this->assertEquals(1237.50, round($cona->skupnaEnergijaRazsvetljava, 2));

        $this->assertEquals(143.88, round($cona->specTransmisijskeIzgube, 2));
        $this->assertEquals(8.18, round($cona->specVentilacijskeIzgube, 2));
        $this->assertEquals(0.212, round($cona->specKoeficientTransmisijskihIzgub, 3));
        $this->assertEquals(31.96, round($cona->specLetnaToplota, 2));
        $this->assertEquals(0.36, round($cona->specLetniHlad, 2));
    }
}
