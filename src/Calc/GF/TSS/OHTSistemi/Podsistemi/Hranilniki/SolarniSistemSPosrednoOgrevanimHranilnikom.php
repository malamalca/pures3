<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Hranilniki;

use App\Lib\Calc;

class SolarniSistemSPosrednoOgrevanimHranilnikom extends PosrednoOgrevanHranilnik
{
    /**
     * Izračun toplotnih izgub
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki cone
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];
        $temperaturaOkolice = $this->znotrajOvoja ? $cona->notranjaTOgrevanje : 13;

        $UA = 0.16 * pow($this->volumen, 0.5);

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stDniDelovanja = $stDni;

            $this->toplotneIzgube[$namen][$mesec] =
                $UA * (60 - $temperaturaOkolice) * $stDniDelovanja * 24 / 1000 * $this->stevilo;

            $potrebnaEnergija = $vneseneIzgube[$mesec] + $this->toplotneIzgube[$namen][$mesec];

            // vračljive izgube v sisem TSV _rww
            $this->vracljiveIzgubeTSV[$mesec] = 0;
            $this->potrebnaElektricnaEnergija[$namen][$mesec] = 0;
        }

        $this->vracljiveIzgube = $this->toplotneIzgube;

        return $this->toplotneIzgube;
    }
}
