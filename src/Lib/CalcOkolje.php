<?php
declare(strict_types=1);

namespace App\Lib;

class CalcOkolje
{
    /**
     * Ocena notranje vlage glede na zunanjo temperaturo
     *
     * @param array<string, \App\Lib\decimal> $zunanjaT Zunanja temperatura po mesecih.
     * @param bool $highOccupancy Povečana vlaga zaradi večjega števila uporabnikov.
     * @return array<string, \App\Lib\decimal>
     */
    public static function mesecnaNotranjaVlaga($zunanjaT, $highOccupancy = false)
    {
        // za visoko gostoto uporabnikov je occupancyDiff = 5°C
        $occupancyDiff = !empty($highOccupancy) ? 5 : 0;

        $ret = [];
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $ret[$mesec] = 35 + $occupancyDiff;
            if ($zunanjaT[$mesec] > 20) {
                $ret[$mesec] = 65 + $occupancyDiff;
            } elseif ($zunanjaT[$mesec] > -10) {
                $ret[$mesec] = 35 + $occupancyDiff + $zunanjaT[$mesec] + 10;
            }
        }

        return $ret;
    }

    /**
     * Ocena notranje temperature glede na zunanjo temperaturo
     *
     * @param array<string, \App\Lib\decimal> $zunanjaT Zunanja temperatura po mesecih.
     * @return array<string, \App\Lib\decimal>
     */
    public static function mesecnaNotranjaTemperatura($zunanjaT)
    {
        $ret = [];
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $ret[$mesec] = 20;
            if ($zunanjaT[$mesec] > 20) {
                $ret[$mesec] = 25;
            } elseif ($zunanjaT[$mesec] > 10) {
                $ret[$mesec] = 20 + ($zunanjaT[$mesec] - 10) * 0.5;
            }
        }

        return $ret;
    }

    /**
     * Izračun tlaka glede na temperaturo in vlago.
     * Annex E.1
     *
     * @param array<string, \App\Lib\decimal> $temperatura Temperatura po mesecih.
     * @param array<string, \App\Lib\decimal> $vlaga Vlaga po mesecih.
     * @return array<string, \App\Lib\decimal>
     */
    public static function mesecniTlak($temperatura, $vlaga)
    {
        $ret = [];
        foreach (array_keys(Calc::MESECI) as $mesec) {
            if ($temperatura[$mesec] < 0) {
                $ret[$mesec] = 610.5 * pow(M_E, 21.875 * $temperatura[$mesec] / (265.5 + $temperatura[$mesec]));
            } else {
                $ret[$mesec] = 610.5 * pow(M_E, 17.269 * $temperatura[$mesec] / (237.3 + $temperatura[$mesec]));
            }

            $ret[$mesec] = $ret[$mesec] * $vlaga[$mesec] / 100;
        }

        return $ret;
    }

    /**
     * Izračun nasičenega tlaka
     *
     * @param array<string, \App\Lib\decimal> $tlak Tlak po mesecih
     * @return array<string, \App\Lib\decimal>
     */
    public static function mesecniNasicenTlak($tlak)
    {
        $ret = [];
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $ret[$mesec] = $tlak[$mesec] / 0.8;
        }

        return $ret;
    }

    /**
     * Izračun minimalne temperature notranje površine
     *
     * @param array<string, \App\Lib\decimal> $nasicenTlak Nasičen tlak po mesecih
     * @return array<string, \App\Lib\decimal>
     */
    public static function mesecnaMinTSi($nasicenTlak)
    {
        $ret = [];
        foreach (array_keys(Calc::MESECI) as $mesec) {
            if ($nasicenTlak[$mesec] < 610.5) {
                $ret[$mesec] = 265.5 * log($nasicenTlak[$mesec] / 610.5) / (21.875 - log($nasicenTlak[$mesec] / 610.5));
            } else {
                $ret[$mesec] = 237.3 * log($nasicenTlak[$mesec] / 610.5) / (17.269 - log($nasicenTlak[$mesec] / 610.5));
            }
        }

        return $ret;
    }

    /**
     * Izračun minimalnega faktorja f_Rsi
     *
     * @param array<string, \App\Lib\decimal> $zunanjaT Zunanja temperatura po mesecih
     * @param array<string, \App\Lib\decimal> $notranjaT Notranja temperatura po mesecih
     * @param array<string, \App\Lib\decimal> $minTSi Minimalna temperatura na notranji površini po mesecih
     * @return array<string, \App\Lib\decimal>
     */
    public static function mesecniMinFRSi($zunanjaT, $notranjaT, $minTSi)
    {
        $ret = [];
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $ret[$mesec] = ($minTSi[$mesec] - $zunanjaT[$mesec]) / ($notranjaT[$mesec] - $zunanjaT[$mesec]);
        }

        return $ret;
    }

    /**
     * Izračun parametrov notranjega okolja glede na zunanjo temperaturo
     *
     * @param array<string, array> $params Fiksni parametri. Min zahteva je "zunanjaT" in "zunanjaVlaga"
     * @param array $options Options to set
     * @return array<string, array>
     */
    public static function notranjeOkolje($params, $options = [])
    {
        $ret = new \StdClass();
        $ret->zunanjaT = $params['zunanjaT'];
        $ret->zunanjaVlaga = $params['zunanjaVlaga'] ?? null;

        if (empty($params['notranjaT'])) {
            $ret->notranjaT = self::mesecnaNotranjaTemperatura($ret->zunanjaT);
        } else {
            $ret->notranjaT = $params['notranjaT'];
        }

        if (empty($params['notranjaVlaga'])) {
            $ret->notranjaVlaga = self::mesecnaNotranjaVlaga($ret->zunanjaT, !empty($options['highOccupancy']));
        } else {
            $ret->notranjaVlaga = $params['notranjaVlaga'];
        }

        $ret->tlak = self::mesecniTlak($ret->notranjaT, $ret->notranjaVlaga);
        $ret->nasicenTlak = self::mesecniNasicenTlak($ret->tlak);
        $ret->minTSi = self::mesecnaMinTSi($ret->nasicenTlak);

        $ret->minfRsi = self::mesecniMinFRSi($ret->zunanjaT, $ret->notranjaT, $ret->minTSi);
        $ret->limitfRsi = max($ret->minfRsi);

        return $ret;
    }
}
