<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi;

use App\Lib\Calc;

class HladilniSistemSHladnoVodo extends OHTSistem
{
    /**
     * @inheritDoc
     */
    public function standardnaMoc($cona, $okolje): float
    {
        return $this->generatorji[0]->nazivnaMoc;
    }

    /**
     * @inheritDoc
     */
    public function steviloUrDelovanja($mesec, $cona, $okolje): float
    {
        $stDni = Calc::steviloDni($mesec);
        $stUr = 24 * $stDni;

        $potrebnaEnergija = $cona->energijaHlajenje[$mesec] + $cona->energijaRazvlazevanje[$mesec];
        $ret = ceil($potrebnaEnergija / $stDni / $this->standardnaMoc($cona, $okolje)) * $stDni;

        return $ret;
    }
}
