<?php

namespace App\Calc\TSS\KoncniPrenosniki;

abstract class KoncniPrenosnik {

    protected int $stOgreval = 1;

    public function __construct($config = null)
    {
        if ($config) {
            $this->parseConfig($config);
        }
    }

    public function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->stOgreval = $config->stOgreval ?? 1;

        // Δθhydr - deltaTemp za hidravlično uravnoteženje sistema; prvi stolpec za stOgreval <= 10, drugi za > 10
        $config->hidravlicnoUravnotezenje = $config->hidravlicnoUravnotezenje ?? 'neuravnotezeno';
        $hidrFaktorji = [
            'neuravnotezeno' => [0.6, 0.6],
            'staticnoKoncnihPrenosnikov' => [0.3, 0.4],
            'staticnoDviznihVodov' => [0.2, 0.3],
            'dinamicnoPolnaObremenitev' => [0.1, 0.2],
            'dinamicnoDelnaObremenitev' => [0, 0],
        ];
        $indexHidrFaktorji = $stOgreval > 10 ? 1 : 0;
        $deltaT_hidravlicnoUravnotezenje = $hidrFaktorji[$prenosnik->hidravlicnoUravnotezenje][$indexHidrFaktorji];

    }

    public function analizaIzgub($cona)
    {
        
    }
}