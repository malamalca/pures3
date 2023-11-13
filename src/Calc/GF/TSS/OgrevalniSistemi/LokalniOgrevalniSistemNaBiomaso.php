<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi;

use App\Calc\GF\Cone\Cona;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\PecNaDrva;
use App\Calc\GF\TSS\TSSVrstaEnergenta;
use App\Lib\Calc;

class LokalniOgrevalniSistemNaBiomaso extends OgrevalniSistem
{
    private const STEVILO_ITERACIJ = 0;

    public float $nazivnaMoc;
    public float $izkoristek;

    public PecNaDrva $koncniPrenosnik;

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    protected function parseConfig($config)
    {
        parent::parseConfig($config);

        if (is_string($config)) {
            $config = json_decode($config);
        }

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

    /**
     * Analiza ogrevalnega sistem
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public function analiza($cona, $okolje)
    {
        $vracljiveIzgube = $this->vracljiveIzgubeVOgrevanje;

        // iteracija za vračljive izgube
        for ($i = 0; $i <= self::STEVILO_ITERACIJ; $i++) {
            // ponovno poračunam potrebno energijo za ogrevanje
            $spremembaCone = new Cona(null, $cona);
            $spremembaCone->vracljiveIzgube = $vracljiveIzgube;
            $spremembaCone->izracunFaktorjaIzkoristka();
            $spremembaCone->izracunEnergijeOgrevanjeHlajanje();
            $cona = $spremembaCone->export();
            $this->init($cona, $okolje);

            $this->potrebnaEnergija = $cona->energijaOgrevanje;
            $this->potrebnaElektricnaEnergija = [];

            foreach ($this->koncniPrenosniki as $prenosnik) {
                $prenosnik->analiza($this->potrebnaEnergija, $this, $cona, $okolje);

                $this->potrebnaEnergija = array_sum_values($this->potrebnaEnergija, $prenosnik->toplotneIzgube);

                $this->potrebnaElektricnaEnergija = array_sum_values(
                    $this->potrebnaElektricnaEnergija,
                    $prenosnik->potrebnaElektricnaEnergija
                );

                $vracljiveIzgube = array_sum_values($vracljiveIzgube, $prenosnik->vracljiveIzgube);
                $vracljiveIzgube = array_sum_values($vracljiveIzgube, $prenosnik->vracljiveIzgubeAux);
            }

            // upoštevam še izkoristek sistema in fHs (AI248) glede na vrsto energenta
            $f = 1.08;
            $this->potrebnaEnergija = array_map(fn($mesec) => $mesec / $this->izkoristek / $f, $this->potrebnaEnergija);
        }

        $this->energijaPoEnergentih[TSSVrstaEnergenta::Biomasa->value] =
            array_sum($this->potrebnaEnergija);

        $this->letnaUcinkovitostOgrHlaTsv =
            $cona->skupnaEnergijaOgrevanje /
            $this->energijaPoEnergentih[TSSVrstaEnergenta::Biomasa->value];

        $this->minLetnaUcinkovitostOgrHlaTsv = TSSVrstaEnergenta::Biomasa->minimalniIzkoristekOgrHlaTsv();
    }
}
