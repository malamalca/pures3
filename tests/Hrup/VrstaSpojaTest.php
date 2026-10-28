<?php
declare(strict_types=1);

namespace App\Test\Hrup;

use App\Calc\Hrup\ZracniHrup\Izbire\VrstaSpoja;
use PHPUnit\Framework\TestCase;

class VrstaSpojaTest extends TestCase
{
    /**
     * SIST EN ISO 12354-1:2017, Priloga E.3.5, slika E.7 (lahka dvojna stena s homogenim elementom).
     */
    public function testSpojLahkeDvojneSteneSHomogenimElementom(): void
    {
        $M = 0.75;
        $K = VrstaSpoja::SpojLahkeDvojneSteneSHomogenimElementom->faktorjiK($M);

        $this->assertEqualsWithDelta(3.0 + 14.1 * $M + 5.7 * $M * $M, $K['K24'], 0.001);
        $this->assertEqualsWithDelta(16.78125, $K['K24'], 0.001);
        $this->assertEqualsWithDelta(10 + 10 * $M, $K['K12'], 0.001);
        $this->assertEqualsWithDelta(25.0, $K['K13'], 0.001);
        $this->assertEqualsWithDelta($K['K12'], $K['K23'], 0.001);
    }
}
