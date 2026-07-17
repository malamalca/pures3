<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi;

use App\Lib\Calc;

class ToplovodniOHTSistem extends OHTSistem
{
    /**
     * @inheritDoc
     */
    public function standardnaMoc($cona, $okolje): float
    {
        $standardnaMoc = ($cona->specTransmisijskeIzgube + $cona->specVentilacijskeIzgube) *
            ($cona->notranjaTOgrevanje - $okolje->projektnaZunanjaT) / 1000;

        return $standardnaMoc;
    }

    /**
     * @inheritDoc
     */
    public function steviloUrDelovanja($mesec, $cona, $okolje): float
    {
        $stDni = Calc::steviloDni($mesec);
        $stUr = 24 * $stDni;

        // betaH - Izračun povprečnih obremenitev podsistemov
        $povprecnaObremenitev = $cona->energijaOgrevanje[$mesec] / ($this->standardnaMoc($cona, $okolje) * $stUr);

        $ret = $stUr * ($povprecnaObremenitev > 0.05 ? 1 : $povprecnaObremenitev / 0.05);

        return $ret;
    }
}
