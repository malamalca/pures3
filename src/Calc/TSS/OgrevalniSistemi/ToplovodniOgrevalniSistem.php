<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi;

use App\Calc\TSS\EnergentFactory;
use App\Calc\TSS\TSSVrstaEnergenta;
use App\Lib\Calc;

class ToplovodniOgrevalniSistem extends OgrevalniSistem
{
    public string $namen;

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

        $this->energent = EnergentFactory::create($config->energent ?? 'default');
        $this->namen = $config->namen ?? 'ogrevanje';
    }

    /**
     * Inicializacija parametrov sistema
     *
     * @param \stdClass $cona Podatki cone
     * @return void
     */
    public function init($cona)
    {
        $this->standardnaMoc = ($cona->specTransmisijskeIzgube + $cona->specVentilacijskeIzgube) *
            ($cona->notranjaTOgrevanje - $cona->zunanjaT) / 1000;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stUr = 24 * $stDni;

            // betaH - Izračun povprečnih obremenitev podsistemov
            $this->povprecnaObremenitev[$mesec] = $cona->energijaOgrevanje[$mesec] / ($this->standardnaMoc * $stUr);
        }
    }

    /**
     * Glavna metoda za analizo ogrevalnega sistema
     *
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return array
     */
    public function analiza($cona, $okolje)
    {
        $this->init($cona);

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $this->energijaPoEnergentih = [];

        $vneseneIzgube = $cona->energijaOgrevanje;
        if ($this->namen == 'ogrevanje') {
            $vneseneIzgube = $cona->energijaOgrevanje;
        }
        if ($this->namen == 'TSV') {
            $vneseneIzgube = $cona->potrebaTSV;
        }

        $potrebnaElektricnaEnergija = [];

        foreach ($this->koncniPrenosniki as $koncniPrenosnik) {
            $izgubePrenosnika = $koncniPrenosnik->toplotneIzgube($vneseneIzgube, $this, $cona, $okolje);

            $elektricnaEnergijaPrenosnika =
                $koncniPrenosnik->potrebnaElektricnaEnergija($vneseneIzgube, $this, $cona, $okolje);

            $vracljiveIzgubeAux =
                $koncniPrenosnik->vracljiveIzgubeAux($vneseneIzgube, $this, $cona, $okolje);

            // seštejem vse izgube prenosnikov
            foreach ($izgubePrenosnika as $k => $v) {
                $vneseneIzgube[$k] += $v;
            }
            foreach ($elektricnaEnergijaPrenosnika as $k => $v) {
                $potrebnaElektricnaEnergija[$k] = ($potrebnaElektricnaEnergija[$k] ?? 0) + $v;
            }

            foreach ($vracljiveIzgubeAux as $k => $v) {
                $this->vracljiveIzgube[$k] = ($this->vracljiveIzgube[$k] ?? 0) + $v;
            }
        }

        foreach ($this->razvodi as $razvod) {
            $prenosnik = array_filter($this->koncniPrenosniki, fn($p) => $p->id == $razvod->idPrenosnika);
            if (!empty($prenosnik)) {
                $prenosnik = reset($prenosnik);
            }

            $izgubeRazvoda =
                $razvod->toplotneIzgube($vneseneIzgube, $this, $cona, $okolje, ['prenosnik' => $prenosnik]);

            $elektricnaEnergijaRazvoda =
                $razvod->potrebnaElektricnaEnergija($vneseneIzgube, $this, $cona, $okolje, ['prenosnik' => $prenosnik]);

            // dodam k vnesenim izgubam
            foreach ($izgubeRazvoda as $k => $v) {
                $vneseneIzgube[$k] += $v;
            }
            foreach ($elektricnaEnergijaRazvoda as $k => $v) {
                $potrebnaElektricnaEnergija[$k] = ($potrebnaElektricnaEnergija[$k] ?? 0) + $v;
            }
            foreach ($razvod->vracljiveIzgube as $k => $v) {
                $this->vracljiveIzgube[$k] = ($this->vracljiveIzgube[$k] ?? 0) + $v;
            }
            foreach ($razvod->vracljiveIzgubeAux as $k => $v) {
                $this->vracljiveIzgube[$k] = ($this->vracljiveIzgube[$k] ?? 0) + $v;

                // TODO: eno so vračljive izgube v okolico, druge v sistem
                $vneseneIzgube[$k] -= $v;
            }
        }

        foreach ($this->hranilniki as $hranilnik) {
            $izgubeHranilnika = $hranilnik->toplotneIzgube($vneseneIzgube, $this, $cona, $okolje);

            // dodam k vnesenim izgubam
            foreach ($izgubeHranilnika as $k => $v) {
                $vneseneIzgube[$k] += $v;
            }

            foreach ($hranilnik->vracljiveIzgube as $k => $v) {
                //$this->vracljiveIzgube[$k] = ($this->vracljiveIzgube[$k] ?? 0) + $v;
            }
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // izgube ogrevala
        foreach ($this->generatorji as $generator) {
            $izgubeGeneratorja = $generator->toplotneIzgube($vneseneIzgube, $this, $cona, $okolje);

            $elektricnaEnergijaGeneratorja =
                $generator->potrebnaElektricnaEnergija($vneseneIzgube, $this, $cona, $okolje);

            $this->obnovljivaEnergija =
                $generator->obnovljivaEnergija($vneseneIzgube, $this, $cona, $okolje);

            // obračun energije po energentihž
            // dodam k vnesenim izgubam
            foreach ($izgubeGeneratorja as $k => $v) {
                $vneseneIzgube[$k] += $v;
            }
            foreach ($elektricnaEnergijaGeneratorja as $k => $v) {
                $potrebnaElektricnaEnergija[$k] = ($potrebnaElektricnaEnergija[$k] ?? 0) + $v;
            }
        }

        $this->potrebnaEnergija = $vneseneIzgube;
        $this->potrebnaElektricnaEnergija = $potrebnaElektricnaEnergija;

        foreach ($this->potrebnaEnergija as $k => $v) {
            $this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] =
                ($this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] ?? 0) + $v;
        }

        foreach ($this->obnovljivaEnergija as $k => $v) {
            $this->energijaPoEnergentih[TSSVrstaEnergenta::Okolje->value] =
                ($this->energijaPoEnergentih[TSSVrstaEnergenta::Okolje->value] ?? 0) + $v;
        }

        // od skupne energije odštejemo energijo okolja
        $this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] =
            ($this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] ?? 0) -
            ($this->energijaPoEnergentih[TSSVrstaEnergenta::Okolje->value] ?? 0);

        foreach ($this->potrebnaElektricnaEnergija as $k => $v) {
            $this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] =
                $this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] + $v;
        }

        return [];
    }
}
