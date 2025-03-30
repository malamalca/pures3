<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire;

enum VrstaSSE: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Nezasteklen = 'nezastekljen';
    case Zastekljen = 'zastekljen';
    case VakuumskiSPloscatimAbosrberjem = 'vakuumskiPloscatiAbsorber';
    case VakuumskiSCevnimAbosrberjem = 'vakuumskiCevniAbsorber';

    /**
     * Friendly name
     *
     * @return string
     */
    public function toString()
    {
        $lookup = [
            'Nezastekljen SSE',
            'Zastekljen SSE',
            'Vakuumski SSE s ploščatim absorberjem',
            'Vakuumski SSE s cevnim absorberjem',
        ];

        return $lookup[$this->getOrdinal()];
    }

    /**
     * a1
     *
     * @return float
     */
    public function a1(): float
    {
        $a1Data = [15.0, 3.5, 1.8, 1.8];

        return $a1Data[$this->getOrdinal()];
    }

    /**
     * a2
     *
     * @return float
     */
    public function a2(): float
    {
        return 0;
    }

    /**
     * IAM
     *
     * @return float
     */
    public function IAM(): float
    {
        $a1Data = [1.0, 0.94, 0.97, 1];

        return $a1Data[$this->getOrdinal()];
    }

    /**
     * eta0
     *
     * @return float
     */
    public function eta0(): float
    {
        return 0.8;
    }
}
