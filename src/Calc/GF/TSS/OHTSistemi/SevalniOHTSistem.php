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
        $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
        $stUr = 24 * $stDni;

        // betaH - Izračun povprečnih obremenitev podsistemov
        $povprecnaObremenitev = $cona->energijaOgrevanje[$mesec] / ($this->standardnaMoc($cona, $okolje) * $stUr);

        $ret = $stUr * ($povprecnaObremenitev > 0.05 ? 1 : $povprecnaObremenitev / 0.05);

        return $ret;
    }
}
