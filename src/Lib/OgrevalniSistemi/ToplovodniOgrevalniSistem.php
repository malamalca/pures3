<?php

namespace App\Lib\OgrevalniSistemi;

use App\Lib\Calc;
use App\Lib\TSSOgrevanjeEnergentFactory;
use App\Lib\TSSOgrevanjeOgrevalniSistem;
use App\Lib\TSSOgrevanjeRazvodFactory;


class ToplovodniOgrevalniSistem extends TSSOgrevanjeOgrevalniSistem {

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

        if (empty($config->energent)) {
            throw new \Exception('Ni vpisanega energenta za TSS ogrevanje.');
        }

        $this->energent = TSSOgrevanjeEnergentFactory::create($config->energent);

        if (!empty($config->razvodi)) {
            foreach ($config->razvodi as $razvod) {
                $this->razvodi[] = TSSOgrevanjeRazvodFactory::create($razvod->vrsta, $razvod);

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

        $Cm_eff = $cona->ogrevanaPovrsina * $cona->toplotnaKapaciteta;
        $tau_ogrevanje = $Cm_eff / 3600 / ($cona->specTransmisijskeIzgube + $cona->specVentilacijskeIzgube);
        $a_ogrevanje = 1 + ($tau_ogrevanje / 15);

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $vracljiveIzgube = 0;

            $gama = ($cona->solarniDobitkiOgrevanje[$mesec] + $cona->notranjiViriOgrevanje[$mesec] + $vracljiveIzgube) /
                ($cona->transIzgubeOgrevanje[$mesec] + $cona->prezracevalneIzgubeOgrevanje[$mesec]);

            // TODO: Preveri... v excelu V150 je čuden pogoj za izračun $gama
            $ucinekDobitkov = null;
            if ($gama > 0 && $gama < 2) {
                if ($gama == 1) {
                    $ucinekDobitkov = $a_ogrevanje / ($a_ogrevanje + 1);
                } else {
                    $ucinekDobitkov = (1 - pow($gama, $a_ogrevanje)) / (1 - pow($gama, $a_ogrevanje + 1));
                }
            }

            // QH,nd,m; QH,nd,an
            $this->izgube[$mesec] = empty($ucinekDobitkov) ? 0 :
                $cona->transIzgubeOgrevanje[$mesec] + $cona->prezracevalneIzgubeOgrevanje[$mesec] -
                $ucinekDobitkov *
                ($cona->solarniDobitkiOgrevanje[$mesec] + $cona->notranjiViriOgrevanje[$mesec] + $vracljiveIzgube);
        }

        foreach ($config->razvodi as $razvod) {
            $razvod->analiza();
        }
    }
}

?>