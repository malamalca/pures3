<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi;

class LokalniOHTSistemNaBiomaso extends OHTSistem
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
        foreach ($config->prenosniki as $prenosnik) {
            $config->ogrevanje->prenosniki[] = $prenosnik->id;
        }

        $config->energent = 'biomasa';

        parent::parseConfig($config);

        $this->nazivnaMoc = $config->nazivnaMoc;
        $this->izkoristek = $config->izkoristek ?? 1;
    }

    /**
     * @inheritDoc
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

    /**
     * @inheritDoc
     */
    public function steviloUrDelovanja($mesec, $cona, $okolje): float
    {
        $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
        $stUr = 24 * $stDni;

        // betaH - Izračun povprečnih obremenitev podsistemov
        $povprecnaObremenitev = $cona->energijaOgrevanje[$mesec] / ($this->standardnaMoc($cona, $okolje) * $stUr);

        $ret = $stUr * ($povprecnaObremenitev > 0.05 ? 1 : $povprecnaObremenitev / 0.05);

        return $ret;
    }
}
