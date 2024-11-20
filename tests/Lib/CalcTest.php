<?php
declare(strict_types=1);

namespace App\Test\Lib;

use App\Lib\Calc;
use PHPUnit\Framework\TestCase;

/**
 * App\Lib\CalcTest Test Case
 */
class CalcTest extends TestCase
{
    public function testIzracunajRw()
    {
        $R = [20.4, 16.3, 17.7, 22.6, 22.4, 22.7, 24.8, 26.6, 28.0, 30.5, 31.8, 32.5, 33.4, 33.0, 31.0, 25.5];

        $Rw = Calc::Rw($R);

        $this->assertEquals(30, $Rw);
    }

    public function testIzracunajC()
    {
        $R = [20.4, 16.3, 17.7, 22.6, 22.4, 22.7, 24.8, 26.6, 28.0, 30.5, 31.8, 32.5, 33.4, 33.0, 31.0, 25.5];

        $C = Calc::C($R);

        $this->assertEquals(-2, $C);
    }

    public function testIzracunajCtr()
    {
        $R = [20.4, 16.3, 17.7, 22.6, 22.4, 22.7, 24.8, 26.6, 28.0, 30.5, 31.8, 32.5, 33.4, 33.0, 31.0, 25.5];

        $Ctr = Calc::Ctr($R);

        $this->assertEquals(-3, $Ctr);
    }
}
