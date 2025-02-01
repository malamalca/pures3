<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi;

class ToplovodniOHTSistem extends OHTSistem
{

    /**
     * @inheritdoc
     */
    public function standardnaMoc($cona, $okolje): float
    {
        $standardnaMoc = ($cona->specTransmisijskeIzgube + $cona->specVentilacijskeIzgube) *
            ($cona->notranjaTOgrevanje - $okolje->projektnaZunanjaT) / 1000;

        return $standardnaMoc;
    }
}
