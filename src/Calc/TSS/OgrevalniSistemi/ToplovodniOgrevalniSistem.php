<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi;

use App\Calc\TSS\EnergentFactory;

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

        $izgube = [];

        foreach ($this->koncniPrenosniki as $koncniPrenosnik) {
            $izgubePrenosnika = $koncniPrenosnik->toplotneIzgube($cona, $okolje);
            foreach ($izgubePrenosnika as $k => $v) {
                $this->izgubePrenosnikov[$k] = ($this->izgubePrenosnikov[$k] ?? 0) + $v;
            }
        }

        foreach ($this->razvodi as $razvod) {
            $prenosnik = array_filter($this->koncniPrenosniki, fn($p) => $p->id == $razvod->idPrenosnika);
            if (empty($prenosnik)) {
                throw new \Exception(sprintf('Prenosnik %s na obstaja.', $razvod->idPrenosnika));
            }
            $prenosnik = reset($prenosnik);

            $izgubeRazvoda = $razvod->toplotneIzgube($prenosnik, $this, $cona, $okolje);

            $elektrika = $razvod->potrebnaElektricnaEnergija($prenosnik, $this, $cona);
        }

        // izgube ogrevala
        return [];
    }
}
