<?php

namespace App\Calc\TSS\OgrevalniSistemi;

use App\Lib\Calc;
use App\Calc\TSS\EnergentFactory;
use App\Calc\TSS\KoncniPrenosnikFactory;
use App\Calc\TSS\RazvodFactory;

class ToplovodniOgrevalniSistem extends OgrevalniSistem {

    /**
     * QN – standardna potrebna toplotna moč za ogrevanje (cone) – moč ogreval, skladno s SIST
     * EN 12831 ali z drugimi enakovrednimi, v stroki priznanimi računskimi metodami [kW]
     * 
     * @var float $standardnaMoc
     * 
     */
    public float $standardnaMoc;

    protected function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->energent = EnergentFactory::create($config->energent ?? 'default');

        if (!empty($config->razvodi)) {
            foreach ($config->razvodi as $razvod) {
                $this->razvodi[] = RazvodFactory::create($razvod->vrsta, $razvod);
            }
        }

        if (!empty($config->prenosniki)) {
            foreach ($config->prenosniki as $prenosnik) {
                $this->koncniPrenosniki[] = KoncniPrenosnikFactory::create($prenosnik->vrsta, $prenosnik);
            }
        }
    }

    /**
     * Glavna metoda za analizo ogrevanja
     *
     * @param \StdClass $cona Podatki cone
     * @param \StdClass $okolje Podatki okolja
     * @return \StdClass
     */
    public function analiza($cona, $okolje)
    {
        $this->standardnaMoc = ($cona->specTransmisijskeIzgube + $cona->specVentilacijskeIzgube) * 
            ($cona->notranjaTOgrevanje - $cona->zunanjaT) / 1000;

        //var_dump($cona->energijaOgrevanje);

        $izgube = [];

        foreach ($this->koncniPrenosniki as $koncniPrenosnik) {
            $izgubePrenosnika = $koncniPrenosnik->analizaIzgub($cona, $okolje);
        }
    }
}

?>