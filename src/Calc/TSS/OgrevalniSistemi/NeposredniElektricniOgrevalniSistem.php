<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi;

use App\Calc\GF\Cone\Cona;
use App\Calc\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\ElektricnoOgrevalo;
use App\Calc\TSS\TSSVrstaEnergenta;
use App\Lib\Calc;

class NeposredniElektricniOgrevalniSistem extends OgrevalniSistem
{
    private const STEVILO_ITERACIJ = 1;

    public float $nazivnaMoc;
    public float $izkoristek;

    public ElektricnoOgrevalo $koncniPrenosnik;

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
        $vracljiveIzgube = [];

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

            // upoštevam še izkoristek sistema
            $this->potrebnaEnergija = array_map(fn($mesec) => $mesec / $this->izkoristek, $this->potrebnaEnergija);
        }

        $this->energijaPoEnergentih['ogrevanje'][TSSVrstaEnergenta::Elektrika->value] =
            array_sum($this->potrebnaEnergija);

        $this->letnaUcinkovitostOgrHlaTsv =
            $cona->skupnaEnergijaOgrevanje /
            ($this->energijaPoEnergentih['ogrevanje'][TSSVrstaEnergenta::Elektrika->value] *
            TSSVrstaEnergenta::Elektrika->utezniFaktor('tot'));

        $this->minLetnaUcinkovitostOgrHlaTsv = TSSVrstaEnergenta::Elektrika->minimalniIzkoristekOgrHlaTsv();
    }
}
