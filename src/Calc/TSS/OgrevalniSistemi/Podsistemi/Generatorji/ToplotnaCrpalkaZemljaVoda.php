<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi\Podsistemi\Generatorji;

use App\Lib\Calc;

class ToplotnaCrpalkaZemljaVoda extends Generator
{
    public const REZIMI = ['w-7', 'w2', 'w7', 'w10', 'w20'];
    public const REZIMI_TEMPERATURE = [-7, 2, 7, 10, 20];

    public const RELATIVNA_MOC = [
        20 => [0.76, 0.92, 1.08, 1.15, 1.25],
        35 => [0.72, 0.88, 1.04, 1.12, 1.25],
        40 => [0.71, 0.87, 1.03, 1.11, 1.25],
        55 => [0.67, 0.83, 0.99, 1.07, 1.27],
    ];

    public const FAKTOR_RADIATORJEV = [
        0 => 0.801, 10 => 0.801, 20 => 0.801, 30 => 0.801, 40 => 0.801, 50 => 0.801,
        60 => 0.848, 70 => 0.891, 80 => 0.922, 90 => 0.955, 100 => 0.955,
    ];

    public float $nazivniCOP;

    public float $elektricnaMocNaPrimarnemKrogu;
    public float $elektricnaMocNaSekundarnemKrogu;

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

        $this->elektricnaMocNaPrimarnemKrogu = $config->elektricnaMocNaPrimarnemKrogu ?? 0;
        $this->elektricnaMocNaSekundarnemKrogu = $config->elektricnaMocNaSekundarnemKrogu ?? 0;
    }

    /**
     * Izračun potrebne energije generatorja
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function potrebnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $relativnaMoc = [];
        $dejanskaMoc = [];
        $cop = [];
        $rezimRazvoda = $params['rezim'];
        $namen = $params['namen'];

        $rezimi = self::REZIMI;

        // za ogrevanje ne upoštevamo režima 20°
        if ($namen != 'tsv') {
            unset($rezimi[4]);
        }

        $this->vneseneIzgube[$namen] = $vneseneIzgube;

        foreach ($rezimi as $ix => $rezim) {
            $relativnaMoc[$rezim] = self::RELATIVNA_MOC[$rezimRazvoda->temperaturaPonora()][$ix];
            $dejanskaMoc[$rezim] = self::RELATIVNA_MOC[$rezimRazvoda->temperaturaPonora()][$ix] * $this->nazivnaMoc;

            $temperaturaEvaporacije = 2;
            $temperaturaKondenzacije = 35;
            $temperaturaPonora = $rezimRazvoda->temperaturaPonora();

            $cop[$rezim] = $this->cop[$rezim] ?? $this->nazivniCOP *
                ($temperaturaPonora + 273.15) / ($temperaturaKondenzacije + 273.15) *
                ($temperaturaKondenzacije - $temperaturaEvaporacije) /
                ($temperaturaPonora - self::REZIMI_TEMPERATURE[$ix]);

            // prilagoditveni faktor glede na režim
            $faktorRezima = 1;
            if ($namen != 'tsv') {
                $faktorRezima = $rezimRazvoda->faktorDeltaTempTC();
            }
            $cop[$rezim] = $cop[$rezim] * $faktorRezima;
        }

        $En = [];
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $sumUr = 0;
            foreach ($rezimi as $k => $rezim) {
                $sumUr += self::TRAJANJE[$this->podnebje][$rezim][$mesec];
            }

            // razdelitev mesečnih vnešenih izgub na posamezne režime
            foreach ($rezimi as $rezim) {
                $trajanjeUr = self::TRAJANJE[$this->podnebje][$rezim][$mesec];

                $potrebnaEnergija[$mesec][$rezim] = $vneseneIzgube[$mesec] * $trajanjeUr / $sumUr;

                // število ur delovanja TČ
                $t_ON_TC = $potrebnaEnergija[$mesec][$rezim] / $dejanskaMoc[$rezim];

                // to vse rabimo za korekcijo copa
                //$FC_i = ($trajanjeUr == 0) ? 0 : $potrebnaEnergija[$mesec][$rezim] / ($dejanskaMoc[$rezim] * $trajanjeUr);
                //$FC_i_round = $FC_i > 10 ? 10 : round($FC_i * 10, 0);

                // TODO: korektura odvisno od vrste prenosnikov
                //  - radiatorji lookup glede na % ($FC_i_round+2) * 10
                //  - ploskovna mokri = 0.985 ostali = 0.975
                //  - za zrak ni korekcije = 1
                if ($namen == 'tsv') {
                    $faktroCOPzaOgrevala = 1;
                } else {
                    $faktroCOPzaOgrevala = 0.985;
                }

                $COP_t = $cop[$rezim] * $faktroCOPzaOgrevala;

                $E_tc[$mesec][$rezim] = $potrebnaEnergija[$mesec][$rezim] / $COP_t;

                if (empty($namen)) {
                    $this->potrebnaEnergija[$mesec] = ($this->potrebnaEnergija[$mesec] ?? 0) + $E_tc[$mesec][$rezim];
                } else {
                    $this->potrebnaEnergija[$namen][$mesec] =
                        ($this->potrebnaEnergija[$namen][$mesec] ?? 0) + $E_tc[$mesec][$rezim];
                }
            }
        }
    }

    /**
     * Uporabljena obnovljiva energija iz okolja
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function obnovljivaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        if (empty($this->potrebnaEnergija)) {
            $this->potrebnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params);
        }

        foreach (array_keys(Calc::MESECI) as $mesec) {
            if (empty($namen)) {
                $this->obnovljivaEnergija[$mesec] = $vneseneIzgube[$mesec] - $this->potrebnaEnergija[$mesec];
            } else {
                $this->obnovljivaEnergija[$namen][$mesec] =
                    $vneseneIzgube[$mesec] - $this->potrebnaEnergija[$namen][$mesec];
            }
        }
    }

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function potrebnaElektricnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $dejanskaMoc = [];
        $cop = [];
        $rezimRazvoda = $params['rezim'];
        $namen = $params['namen'];

        foreach (self::REZIMI as $ix => $rezim) {
            $dejanskaMoc[$rezim] = self::RELATIVNA_MOC[$rezimRazvoda->temperaturaPonora()][$ix] * $this->nazivnaMoc;
        }

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $sumUr = 0;
            foreach (self::REZIMI as $rezim) {
                $sumUr += self::TRAJANJE[$this->podnebje][$rezim][$mesec];
            }

            // razdelitev mesečnih vnešenih izgub na posamezne režime
            $delovanjeUr = 0;
            foreach (self::REZIMI as $rezim) {
                $trajanjeUr = self::TRAJANJE[$this->podnebje][$rezim][$mesec];
                $potrebnaEnergija[$mesec][$rezim] = $vneseneIzgube[$mesec] * $trajanjeUr / $sumUr;
                $delovanjeUr += $potrebnaEnergija[$mesec][$rezim] / $dejanskaMoc[$rezim];
            }

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
        $sistem = parent::export();
        $sistem->podnebje = $this->podnebje;
        $sistem->nazivnaMoc = $this->nazivnaMoc;
        $sistem->elektricnaMocNaPrimarnemKrogu = $this->elektricnaMocNaPrimarnemKrogu;
        $sistem->elektricnaMocNaSekundarnemKrogu = $this->elektricnaMocNaSekundarnemKrogu;

        return $sistem;
    }
}
