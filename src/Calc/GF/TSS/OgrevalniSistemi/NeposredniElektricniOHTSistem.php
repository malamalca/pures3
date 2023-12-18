<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi;

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
     * Inicializacija parametrov sistema
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public function init($cona, $okolje)
    {
        $this->standardnaMoc = ($cona->specTransmisijskeIzgube + $cona->specVentilacijskeIzgube) *
            ($cona->notranjaTOgrevanje - $okolje->projektnaZunanjaT) / 1000;

        $moc = $this->standardnaMoc;
        if ($this->nazivnaMoc < $this->standardnaMoc) {
            $moc = $this->nazivnaMoc;
        }

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = 24 * $stDni;

            // betaH - Izračun povprečnih obremenitev podsistemov
            $this->povprecnaObremenitev[$mesec] = $cona->energijaOgrevanje[$mesec] / ($moc * $stUr);
        }
    }
}
