<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi;

use App\Calc\TSS\EnergentFactory;
use App\Lib\Calc;

class ToplovodniOgrevalniSistem extends OgrevalniSistem
{
    /**
     * Loads configuration from json|StdClass
     *
     * @param string|\StdClass $config Configuration
     * @return void
     */
    protected function parseConfig($config)
    {
        $this->energent = EnergentFactory::create($config->energent ?? 'default');

        parent::parseConfig($config);
    }

    /**
     * Glavna metoda za analizo ogrevalnega sistema
     *
     * @param \StdClass $cona Podatki cone
     * @param \StdClass $okolje Podatki okolja
     * @return array
     */
    public function analiza($cona, $okolje)
    {
        $this->standardnaMoc = ($cona->specTransmisijskeIzgube + $cona->specVentilacijskeIzgube) *
            ($cona->notranjaTOgrevanje - $cona->zunanjaT) / 1000;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = 24 * $stDni;

            // betaH - Izračun povprečnih obremenitev podsistemov
            $this->povprecnaObremenitev[$mesec] = $cona->energijaOgrevanje[$mesec] / ($this->standardnaMoc * $stUr);
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $vneseneIzgube = $cona->energijaOgrevanje;
        foreach ($this->koncniPrenosniki as $koncniPrenosnik) {
            $izgubePrenosnika = $koncniPrenosnik->toplotneIzgube($vneseneIzgube, $this, $cona, $okolje);

            // seštejem vse izgube prenosnikov
            foreach ($izgubePrenosnika as $k => $v) {
                $vneseneIzgube[$k] += $v;
            }
        }

        foreach ($this->razvodi as $razvod) {
            $prenosnik = array_filter($this->koncniPrenosniki, fn($p) => $p->id == $razvod->idPrenosnika);
            if (empty($prenosnik)) {
                throw new \Exception(sprintf('Prenosnik %s ne obstaja.', $razvod->idPrenosnika));
            }
            $prenosnik = reset($prenosnik);

            $izgubeRazvoda =
                $razvod->toplotneIzgube($vneseneIzgube, $this, $cona, $okolje, ['prenosnik' => $prenosnik]);

            $razvod->potrebnaElektricnaEnergija($vneseneIzgube, $prenosnik, $this, $cona);

            // dodam k vnesenim izgubam
            foreach ($izgubeRazvoda as $k => $v) {
                $vneseneIzgube[$k] += $v;
            }
        }

        // izgube ogrevala
        foreach ($this->generatorji as $generator) {
            $izgubeGeneratorja = $generator->toplotneIzgube($vneseneIzgube, $this, $cona, $okolje);
        }

        return [];
    }
}
