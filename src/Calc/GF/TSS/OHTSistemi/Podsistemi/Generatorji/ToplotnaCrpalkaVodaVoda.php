<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji;

use App\Calc\GF\TSS\TSSPorociloNiz;
use App\Calc\GF\TSS\TSSPorociloPodatek;
use App\Lib\Calc;

class ToplotnaCrpalkaVodaVoda extends Generator
{
    public float $nazivniCOP;
    public float $temperaturaVira;

    public float $elektricnaMocNaPrimarnemKrogu;
    public float $elektricnaMocNaSekundarnemKrogu;

    public array $E_tc = [];

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        parent::parseConfig($config);

        if (!empty($config->nazivniCOP)) {
            $this->nazivniCOP = $config->nazivniCOP;
        }

        $this->temperaturaVira = $config->temperaturaVira ?? 10;

        if (!($this->temperaturaVira == 10 || $this->temperaturaVira == 15)) {
            throw new \Exception('Temperatura vira mora biti 10 ali 15°C.');
        }

        $this->elektricnaMocNaPrimarnemKrogu = $config->elektricnaMocNaPrimarnemKrogu ?? 0;
        $this->elektricnaMocNaSekundarnemKrogu = $config->elektricnaMocNaSekundarnemKrogu ?? 0;
    }

    /**
     * Izračun potrebne energije generatorja
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $this->E_tc = [];
        $namen = $params['namen'] ?? 'ogrevanje';

         // prilagoditveni faktor glede na režim
         $faktorRezima = 1;
        if ($namen != 'tsv') {
            $faktorRezima = $sistem->ogrevanje->rezim->faktorDeltaTempTC();
        }
         $dejanskiCOP = $this->nazivniCOP * $faktorRezima;

        // TODO: korektura odvisno od vrste prenosnikov
        //  - radiatorji lookup glede na % ($FC_i_round+2) * 10
        //  - ploskovna mokri = 0.985 ostali = 0.975
        //  - za zrak ni korekcije = 1
        if ($namen == 'tsv') {
            $faktroCOPzaOgrevala = 1;
        } else {
            $faktroCOPzaOgrevala = 0.985;
        }

        $COP_t = $dejanskiCOP * $faktroCOPzaOgrevala;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $E_tc[$mesec] = $vneseneIzgube[$mesec] / $COP_t;

            if (empty($namen)) {
                $this->toplotneIzgube[$mesec] = ($this->toplotneIzgube[$mesec] ?? 0) + 0;
                $this->E_tc[$mesec] = ($this->E_tc[$mesec] ?? 0) + $E_tc[$mesec];
                $this->potrebnaElektricnaEnergija[$mesec] = 0;
            } else {
                $this->toplotneIzgube[$namen][$mesec] = ($this->toplotneIzgube[$namen][$mesec] ?? 0) + 0;
                $this->E_tc[$namen][$mesec] = ($this->E_tc[$namen][$mesec] ?? 0) + $E_tc[$mesec];
                $this->potrebnaElektricnaEnergija[$namen][$mesec] = 0;
            }
        }
    }

    /**
     * Uporabljena obnovljiva energija iz okolja
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function obnovljivaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        if (empty($this->toplotneIzgube)) {
            $this->toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params);
        }

        foreach (array_keys(Calc::MESECI) as $mesec) {
            if (empty($namen)) {
                $this->obnovljivaEnergija[$mesec] = $vneseneIzgube[$mesec] - $this->E_tc[$mesec];
            } else {
                $this->obnovljivaEnergija[$namen][$mesec] = $vneseneIzgube[$mesec] - $this->E_tc[$namen][$mesec];
            }
        }
    }

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function potrebnaElektricnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $dejanskaMoc = [];
        $cop = [];
        $namen = $params['namen'] ?? 'ogrevanje';
        $faktorMoci = $this->temperaturaVira == 10 ? 1.05 : 1.18;

        $Q_tc_dej = $this->nazivnaMoc * $faktorMoci;

        $this->vneseneIzgube[$namen] = $vneseneIzgube;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $delovanjeUr = $vneseneIzgube[$mesec] / $Q_tc_dej;

            if (empty($namen)) {
                $this->potrebnaElektricnaEnergija[$mesec] =
                    ($this->potrebnaElektricnaEnergija[$mesec] ?? 0) +
                    ($this->elektricnaMocNaPrimarnemKrogu + $this->elektricnaMocNaSekundarnemKrogu) *
                    $delovanjeUr / 1000;
            } else {
                $this->potrebnaElektricnaEnergija[$namen][$mesec] =
                    ($this->potrebnaElektricnaEnergija[$namen][$mesec] ?? 0) +
                    ($this->elektricnaMocNaPrimarnemKrogu + $this->elektricnaMocNaSekundarnemKrogu) *
                    $delovanjeUr / 1000;
            }
        }
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $E_tc = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00];
        if (isset($this->E_tc['ogrevanje']) || isset($this->E_tc['tsv']) || isset($this->E_tc['hlajenje'])) {
            if (isset($this->E_tc['ogrevanje'])) {
                $E_tc = array_sum_values($E_tc, $this->E_tc['ogrevanje']);
            }
            if (isset($this->E_tc['tsv'])) {
                $E_tc = array_sum_values($E_tc, $this->E_tc['tsv']);
            }
            if (isset($this->E_tc['hlajenje'])) {
                $E_tc = array_sum_values($E_tc, $this->E_tc['hlajenje']);
            }
        } else {
            $E_tc = $this->E_tc;
        }

        $this->porociloNizi[] = new TSSPorociloNiz(
            'E<sub>TČ</sub>',
            'Energija za delovanje TČ',
            $E_tc,
            1
        );

        $this->porociloPodatki[] = new TSSPorociloPodatek(
            'θ<sub>so</sub>',
            'Temperatura vira',
            $this->temperaturaVira,
            '°C'
        );
        $this->porociloPodatki[] = new TSSPorociloPodatek(
            'P<sub>prim,aux</sub>',
            'El. moč na primarnem krogu',
            $this->elektricnaMocNaPrimarnemKrogu,
            'W'
        );
        $this->porociloPodatki[] = new TSSPorociloPodatek(
            'P<sub>sek,aux</sub>',
            'El. moč na sekundarnem krogu',
            $this->elektricnaMocNaSekundarnemKrogu,
            'W'
        );

        $sistem = parent::export();
        $sistem->nazivnaMoc = $this->nazivnaMoc;
        $sistem->temperaturaVira = $this->temperaturaVira;
        $sistem->elektricnaMocNaPrimarnemKrogu = $this->elektricnaMocNaPrimarnemKrogu;
        $sistem->elektricnaMocNaSekundarnemKrogu = $this->elektricnaMocNaSekundarnemKrogu;

        return $sistem;
    }
}
