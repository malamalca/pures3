<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi;

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
        $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
        $stUr = 24 * $stDni;

        $potrebnaEnergija = $cona->energijaHlajenje[$mesec] + $cona->energijaRazvlazevanje[$mesec];
        $ret = ceil($potrebnaEnergija / $stDni / $this->standardnaMoc($cona, $okolje)) * $stDni;

        return $ret;
    }
}
