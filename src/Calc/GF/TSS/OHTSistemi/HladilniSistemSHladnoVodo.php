<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi;

class HladilniSistemSHladnoVodo extends OHTSistem
{
    /**
     * @inheritdoc
     */
    public function standardnaMoc($cona, $okolje): float
    {
        return $this->generatorji[0]->nazivnaMoc;
    }
}
