<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi;

class SevalniOHTSistem extends OHTSistem
{
    /**
     * Analiza ogrevalnega sistem
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public function analiza($cona, $okolje)
    {
    }

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
