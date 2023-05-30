<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class IzracunNotranjegaOkoljaTest extends TestCase
{
    /**
     * @inheritDoc
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function testValidacijaTSG(): void
    {
        $inputZunanjaT = [-1, 1, 6, 10, 15, 18, 20, 19, 15, 10, 4, 1];
        $inputZunanjaVlaga = [82 , 77, 72, 71, 73, 72, 75, 76, 80, 82, 84, 85];
        $result = \App\Lib\CalcOkolje::notranjeOkolje(['zunanjaT' => $inputZunanjaT, 'zunanjaVlaga' => $inputZunanjaVlaga]);
        $expectedInternalTemp = [20, 20, 20, 20, 22.5, 24, 25, 24.5, 22.5, 20, 20, 20];
        $this->assertEquals($expectedInternalTemp, $result->notranjaT);
        $expectedInternalHum = [44, 46, 51, 55, 60, 63, 65, 64, 60, 55, 49, 46];
        $this->assertEquals($expectedInternalHum, $result->notranjaVlaga);
        $roundedResult = array_map(fn($el) => round($el, 3), $result->minfRsi);
        $expected = [0.557, 0.545, 0.495, 0.409, 0.380, 0.347, 0.312, 0.331, 0.380, 0.409, 0.520, 0.545];
        $this->assertEquals($expected, $roundedResult);
    }

    public function testValidacijaStandard13788_B1(): void
    {
        $inputZunanjaT = [2.4, 2.8, 4.5, 6.7, 9.8, 12.6, 14, 13.7, 11.5, 9, 5, 3.5];
        $result = \App\Lib\CalcOkolje::notranjeOkolje(['zunanjaT' => $inputZunanjaT], ['highOccupancy' => true]);
        $expectedInternalTemp = [20, 20, 20, 20, 20, 21.3, 22.0, 21.85, 20.75, 20, 20, 20];
        $this->assertEquals($expectedInternalTemp, $result->notranjaT);
        $expectedInternalHum = [52.4, 52.8, 54.5, 56.7, 59.8, 62.6, 64, 63.7, 61.5, 59, 55, 53.5];
        $this->assertEquals($expectedInternalHum, $result->notranjaVlaga);
        $roundedResult = array_map(fn($el) => (int)round($el, 0), $result->tlak);
        $expectedInternalPressure = [1225, 1234, 1274, 1325, 1397, 1585, 1691, 1668, 1505, 1379, 1285, 1250];
        $this->assertEquals($expectedInternalPressure, $roundedResult);
        $roundedResult = array_map(fn($el) => (int)round($el, 0), $result->nasicenTlak);
        $expectedSaturatedPressure = [1531, 1542, 1592, 1656, 1747, 1981, 2114, 2085, 1882, 1724, 1607, 1563];
        $this->assertEquals($expectedSaturatedPressure, $roundedResult);
        $roundedResult = array_map(fn($el) => round($el, 1), $result->minTSi);
        $expectedSaturatedPressure = [13.3, 13.5, 13.9, 14.6, 15.4, 17.4, 18.4, 18.2, 16.5, 15.2, 14.1, 13.7];
        $this->assertEquals($expectedSaturatedPressure, $roundedResult);
        $roundedResult = array_map(fn($el) => round($el, 3), $result->minfRsi);
        $expectedSaturatedPressure = [0.622, 0.620, 0.609, 0.591, 0.547, 0.547, 0.549, 0.548, 0.546, 0.561, 0.606, 0.616];
        $this->assertEquals($expectedSaturatedPressure, $roundedResult);
    }

    /**
     * Testna funkcija za primer B2 v standardu.
     */
    public function testValidacijaStandard13788_B2(): void
    {
        $inputZunanjaT = [2.4, 2.8, 4.5, 6.7, 9.8, 12.6, 14, 13.7, 11.5, 9, 5, 3.5];
        $inputNotranjaT = [20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20];
        $inputNotranjaVlaga = [50, 50, 50, 50, 50, 50, 50, 50, 50, 50, 50, 50];
        $result = \App\Lib\CalcOkolje::notranjeOkolje([
            'zunanjaT' => $inputZunanjaT,
            'notranjaT' => $inputNotranjaT,
            'notranjaVlaga' => $inputNotranjaVlaga,
        ]);
        $roundedResult = array_map(fn($el) => (int)round($el, 0), $result->tlak);
        $expectedInternalPressure = [1168, 1168, 1168, 1168, 1168, 1168, 1168, 1168, 1168, 1168, 1168, 1168];
        $this->assertEquals($expectedInternalPressure, $roundedResult);
        $roundedResult = array_map(fn($el) => (int)round($el, 0), $result->nasicenTlak);
        $expectedSaturatedPressure = [1461, 1461, 1461, 1461, 1461, 1461, 1461, 1461, 1461, 1461, 1461, 1461];
        $this->assertEquals($expectedSaturatedPressure, $roundedResult);
        $roundedResult = array_map(fn($el) => round($el, 1), $result->minTSi);
        $expectedSaturatedPressure = [12.6, 12.6, 12.6, 12.6, 12.6, 12.6, 12.6, 12.6, 12.6, 12.6, 12.6, 12.6];
        $this->assertEquals($expectedSaturatedPressure, $roundedResult);
        $roundedResult = array_map(fn($el) => round($el, 3), $result->minfRsi);
        $expectedSaturatedPressure = [0.581, 0.571, 0.524, 0.445, 0.277, 0.003, -0.229, -0.171, 0.132, 0.330, 0.508, 0.553];
        $this->assertEquals($expectedSaturatedPressure, $roundedResult);
    }
}
