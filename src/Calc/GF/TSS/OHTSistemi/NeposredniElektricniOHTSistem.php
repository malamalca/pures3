<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi;

use App\Lib\Calc;

class NeposredniElektricniOHTSistem extends OHTSistem
{
    public float $nazivnaMoc;
    public float $izkoristek;

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    protected function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        // postavim array ogrevanja
        $config->ogrevanje = new \stdClass();
        $config->ogrevanje->prenosniki = [];
        foreach ($config->prenosniki as $prenosnik) {
            $config->ogrevanje->prenosniki[] = $prenosnik->id;
        }

        parent::parseConfig($config);

        $this->nazivnaMoc = $config->nazivnaMoc;
        $this->izkoristek = $config->izkoristek ?? 1;
    }

    /**
     * @inheritdoc
     */
    public function standardnaMoc($cona, $okolje): float
    {
        $standardnaMoc = ($cona->specTransmisijskeIzgube + $cona->specVentilacijskeIzgube) *
            ($cona->notranjaTOgrevanje - $okolje->projektnaZunanjaT) / 1000;

        if ($this->nazivnaMoc < $standardnaMoc) {
            $standardnaMoc = $this->nazivnaMoc;
        }

        return $standardnaMoc;
    }
}
