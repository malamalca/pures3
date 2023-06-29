<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\EvalMath;
use PHPUnit\Framework\TestCase;

/**
 * App\Lib\EvalMath Test Case
 */
class EvalMathTest extends TestCase
{
    /**
     * testSimpleMath method
     *
     * @return void
     */
    public function testSimpleMath()
    {
        $EvalMath = EvalMath::getInstance()->setOptions(['decimalSeparator' => '.', 'thousandsSeparator' => '']);
        $this->assertEquals(12, $EvalMath->e('3*4'));
        $this->assertEquals(14, $EvalMath->e('3.5*4'));
    }

    /**
     * testLocalized method
     *
     * @return void
     */
    public function testLocalized()
    {
        $EvalMath = EvalMath::getInstance()->setOptions(['decimalSeparator' => ',']);
        $this->assertEquals(12, $EvalMath->e('3*4'));
        $this->assertEquals(14, $EvalMath->e('3,5*4'));

        $this->assertEquals(38.4, $EvalMath->e('5*4,2+3*5,8'));
    }

    /**
     * testThousands method
     *
     * @return void
     */
    public function testThousands()
    {
        $EvalMath = EvalMath::getInstance()->setOptions(['decimalSeparator' => '.', 'thousandsSeparator' => ',']);
        $this->assertEquals(1000, $EvalMath->e('1000'));
        $this->assertEquals(1000.23, $EvalMath->e('1000.23'));
        $this->assertEquals(1000.23, $EvalMath->e('1,000.23'));
        $this->assertEquals(1000000.23, $EvalMath->e('1,000,000.23'));
        $this->assertEquals(2000.46, $EvalMath->e('1000.23*2'));
        $this->assertEquals(2000.46, $EvalMath->e('1,000.23*2'));

        $EvalMath = EvalMath::getInstance()->setOptions(['decimalSeparator' => ',', 'thousandsSeparator' => '.']);
        $this->assertEquals(1000, $EvalMath->e('1000'));
        $this->assertEquals(1000.23, $EvalMath->e('1000,23'));
        $this->assertEquals(1000.23, $EvalMath->e('1.000,23'));
        $this->assertEquals(1000000.23, $EvalMath->e('1.000.000,23'));
        $this->assertEquals(2000.46, $EvalMath->e('1000,23*2'));
        $this->assertEquals(2000.46, $EvalMath->e('1.000,23*2'));
    }

    /**
     * testParanthesis method
     *
     * @return void
     */
    public function testParanthesis()
    {
        $EvalMath = EvalMath::getInstance()->setOptions(['decimalSeparator' => '.']);
        $this->assertEquals(14, $EvalMath->e('2+3*4'));
        $this->assertEquals(20, $EvalMath->e('(2+3)*4'));
        $this->assertEquals(42, $EvalMath->e('-8(5/2)^2*(1-sqrt(4))-8'));
        $this->assertEquals(-3, $EvalMath->e('(5-2)*(2-3)'));
    }

    /**
     * testExpressions method
     *
     * @return void
     */
    public function testExpressions()
    {
        $EvalMath = EvalMath::getInstance()->setOptions(['decimalSeparator' => '.']);

        $this->assertEquals(round($EvalMath->e('sin(degtorad(30))'), 1), 0.5);

        $EvalMath->evaluate('pow(base,pwr) = base^pwr');
        $this->assertEquals(16, $EvalMath->e('pow(2;4)'));
    }

    /**
     * testGfx method
     *
     * @return void
     */
    public function testGfxSimple()
    {
        $EvalMath = EvalMath::getInstance()->setOptions(['decimalSeparator' => '.']);

        $tokens = $EvalMath->nfx('(2+3 )* 4');
        $result = $EvalMath->gfx($tokens);
        $this->assertEquals('(2+3)*4', $result);

        $result = $EvalMath->gfx($EvalMath->nfx('-(2-3)'));
        $this->assertEquals('-(2-3)', $result);

        $result = $EvalMath->gfx($EvalMath->nfx('(5-2)*(2-3)'));
        $this->assertEquals('(5-2)*(2-3)', $result);

        $result = $EvalMath->gfx($EvalMath->nfx('-(5-2)*(2-3)'));
        $this->assertEquals('-(5-2)*(2-3)', $result);

        $result = $EvalMath->gfx($EvalMath->nfx('2*sin(-(30-5)*(20+6))'));
        $this->assertEquals('2*sin(-(30-5)*(20+6))', $result);

        $result = $EvalMath->gfx($EvalMath->nfx('2*sin(0.5)*pi'));
        $this->assertEquals('2*sin(0.5)*pi', $result);
    }

    /**
     * testGfx method
     *
     * @return void
     */
    public function testGfx2()
    {
        $EvalMath = EvalMath::getInstance()->setOptions(['decimalSeparator' => '.']);
        $EvalMath->evaluate('Aux=12');
        $EvalMath->evaluate('OtherVariable=4.5');

        $result = $EvalMath->gfx($EvalMath->nfx('2*Aux'));
        $this->assertEquals('2*Aux', $result);

        $result = $EvalMath->gfx($EvalMath->nfx('5* OtherVariable'));
        $this->assertEquals('5*OtherVariable', $result);
    }

    /**
     * testUsedVars method
     *
     * @return void
     */
    public function testUsedVars()
    {
        $EvalMath = EvalMath::getInstance()->setOptions(['decimalSeparator' => '.']);
        $EvalMath->evaluate('Aux=12');
        $EvalMath->evaluate('OtherVariable=4.5');
        $EvalMath->evaluate('Third=9');

        $tokens = $EvalMath->nfx('2*Aux*Third*sin(0.5)*pi');
        $result = $EvalMath->pfx($tokens);
        $this->assertEquals(325.33, round($result, 2));

        $vars = $EvalMath->usedVars($tokens);
        $this->assertEquals(['Aux', 'Third'], $vars);
    }

    /**
     * testGfx testLocalize
     *
     * @return void
     */
    public function testLocalize()
    {
        $EvalMath = EvalMath::getInstance()->setOptions(['decimalSeparator' => ',']);
        $result = $EvalMath->localize(12.5);
        $this->assertEquals('12,5', $result);

        $this->assertEquals('=3,5*4,5', $EvalMath->localize('=3.5*4.5'));
        $this->assertEquals('=3,5*4000,5', $EvalMath->localize('=3.5*4000.5'));
        $this->assertEquals('=3,5*4000,5', $EvalMath->localize('=3.5*4,000.5'));

        $EvalMath = EvalMath::getInstance()->setOptions(['decimalSeparator' => '.']);
        $result = $EvalMath->localize(2.5 * 3);
        $this->assertEquals('7.5', $result);

        $this->assertEquals('=3.5*4.5', $EvalMath->localize('=3.5*4.5'));
        $this->assertEquals('=3.5*4000.5', $EvalMath->localize('=3.5*4000.5'));
        $this->assertEquals('=3.5*4000.5', $EvalMath->localize('=3.5*4,000.5'));
    }
}
