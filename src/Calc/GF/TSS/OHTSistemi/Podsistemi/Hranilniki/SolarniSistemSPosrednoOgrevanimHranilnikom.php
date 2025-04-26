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
        //var_dump($UA);

        // EN 15316-4-3:2017
        // In case of unavailability of the tank heat loss coefficient, the default value can be calculated by (B.2)
        // The heat loss coefficient of the storage tank for the water heating service (6.1.2.5.2) or space heating service (6.1.2.5.3). [W/K]
        $H_sto_ls = 16.66 + 8.33 * pow($this->volumen, 0.4) / 45;
        //var_dump($H_sto_ls);

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stDniDelovanja = $stDni;

            $this->toplotneIzgube[$namen][$mesec] =
                $UA * (60 - $temperaturaOkolice) * $stDniDelovanja * 24 / 1000 * $this->stevilo;

            $potrebnaEnergija = $vneseneIzgube[$mesec] + $this->toplotneIzgube[$namen][$mesec];

            // vračljive izgube v sisem TSV _rww
            $this->vracljiveIzgubeTSV[$mesec] = 0;
            $this->potrebnaElektricnaEnergija[$namen][$mesec] = 0;

            $this->vracljiveIzgube[$namen][$mesec] = $this->znotrajOvoja ? $this->toplotneIzgube[$namen][$mesec] : 0;
        }

        return $this->toplotneIzgube;
    }
}
