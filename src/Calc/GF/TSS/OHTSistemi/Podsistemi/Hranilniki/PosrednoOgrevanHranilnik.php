<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Hranilniki;

use App\Lib\Calc;

class PosrednoOgrevanHranilnik extends Hranilnik
{
    public bool $istiProstorKotGrelnik;
    public bool $znotrajOvoja;

    /**
     * Class Constructor
     *
     * @param \stdClass|string|null $config Configuration
     * @return void
     */
    public function __construct($config = null)
    {
        if ($config) {
            $this->parseConfig($config);
        }
    }

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        parent::parseConfig($config);

        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->istiProstorKotGrelnik = !empty($config->istiProstorKotGrelnik);
        $this->znotrajOvoja = !empty($config->znotrajOvoja);
    }

    /**
     * Analiza podsistema
     *
     * @param array $toplotneIzgube Toplotne izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function analiza($toplotneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $this->toplotneIzgube($toplotneIzgube, $sistem, $cona, $okolje, $params);
    }

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

        // f - vpliv cevne povezave med hranilnikom in grelnikom. Če sta
        // nameščena v istem prostoru, je fpovezava = 1,2. V nasprotnem primeru je fpovezava = 1,
        // toplotne izgube se izračunajo posebej po metodologiji opisani v poglavju 8.2.1.1. in
        // se prištejejo enačbi 122.
        $f_povezava = $this->istiProstorKotGrelnik ? 1.2 : 1;

        // q w,s,l - dnevne toplotne izgube hranilnika v stanju obratovalne pripravljenosti [kWh]. Podatek
        // proizvajalca ali enačba 123a ali 123b.
        if ($this->volumen > 1000) {
            $dnevneIzgube = 0.39 * pow($this->volumen, 0.35) + 0.5;
        } else {
            $dnevneIzgube = 0.8 + 0.02 * pow($this->volumen, 0.77);
        }

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            $this->toplotneIzgube[$namen][$mesec] =
                $f_povezava * $dnevneIzgube * (50 - $temperaturaOkolice) / 45 * $stDni * $this->stevilo;
        }

        $this->vracljiveIzgube = $this->toplotneIzgube;

        return $this->toplotneIzgube;
    }
}
