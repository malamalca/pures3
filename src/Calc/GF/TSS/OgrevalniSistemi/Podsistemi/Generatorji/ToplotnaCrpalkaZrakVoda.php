<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji;

use App\Calc\GF\TSS\TSSPorociloNiz;
use App\Calc\GF\TSS\TSSPorociloPodatek;
use App\Lib\Calc;

class ToplotnaCrpalkaZrakVoda extends Generator
{
    public const REZIMI = ['w-7', 'w2', 'w7', 'w10', 'w20'];
    public const REZIMI_TEMPERATURE = [-7, 2, 7, 10, 20];

    public const TRAJANJE = [
        'celinsko' => [
            'w-7' => [175, 67, 34, 0, 0, 0, 0, 0, 0, 0, 39, 82],
            'w2' => [389, 455, 251, 77, 0, 0, 0, 0, 1, 81, 248, 425],
            'w7' => [144, 125, 184, 197, 15, 0, 0, 0, 47, 105, 154, 117],
            'w10' => [36, 25, 229, 311, 277, 47, 94, 72, 286, 390, 258, 120],
            'w20' => [0, 0, 46, 135, 452, 673, 650, 672, 386, 168, 21, 0],
        ],
        'alpsko' => [
            'w-7' => [288, 66, 104, 0, 0, 0, 0, 0, 0, 4, 106, 223],
            'w2' => [390, 358, 259, 127, 18, 0, 0, 0, 9, 123, 248, 452],
            'w7' => [42, 173, 229, 222, 89, 4, 0, 0, 54, 193, 202, 66],
            'w10' => [24, 75, 118, 270, 338, 203, 113, 162, 297, 323, 150, 3],
            'w20' => [0, 0, 34, 101, 299, 513, 631, 582, 360, 101, 14, 0],
        ],
        'primorsko' => [
            'w-7' => [68, 43, 13, 0, 0, 0, 0, 0, 0, 0, 0, 69],
            'w2' => [317, 267, 199, 63, 7, 3, 0, 0, 0, 41, 57, 259],
            'w7' => [249, 226, 279, 167, 25, 12, 0, 0, 12, 92, 149, 311],
            'w10' => [110, 136, 205, 269, 266, 86, 44, 18, 163, 330, 468, 104],
            'w20' => [0, 0, 48, 221, 446, 619, 700, 726, 545, 281, 46, 1],
        ],
    ];

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

    public string $podnebje = 'alpsko';

    public float $nazivniCOP;

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

        $this->podnebje = $config->podnebje ?? 'alpsko';

        $this->elektricnaMocNaPrimarnemKrogu = $config->elektricnaMocNaPrimarnemKrogu ?? 0;
        $this->elektricnaMocNaSekundarnemKrogu = $config->elektricnaMocNaSekundarnemKrogu ?? 0;
    }

    /**
     * Izračun potrebne energije generatorja
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
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
        $this->E_tc = [];
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
                    $this->potrebnaEnergija[$mesec] = ($this->potrebnaEnergija[$mesec] ?? 0) + 0;
                    $this->E_tc[$mesec] = ($this->E_tc[$mesec] ?? 0) + $E_tc[$mesec][$rezim];
                } else {
                    $this->potrebnaEnergija[$namen][$mesec] =
                        ($this->potrebnaEnergija[$namen][$mesec] ?? 0) + 0;
                    $this->E_tc[$namen][$mesec] = ($this->E_tc[$namen][$mesec] ?? 0) + $E_tc[$mesec][$rezim];
                }
            }
        }
    }

    /**
     * Uporabljena obnovljiva energija iz okolja
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
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
                //$this->obnovljivaEnergija[$mesec] = $vneseneIzgube[$mesec] - $this->potrebnaEnergija[$mesec];
                $this->obnovljivaEnergija[$mesec] = $vneseneIzgube[$mesec] - $this->E_tc[$mesec];
            } else {
                $this->obnovljivaEnergija[$namen][$mesec] = $vneseneIzgube[$mesec] - $this->E_tc[$namen][$mesec];
                //$this->obnovljivaEnergija[$namen][$mesec] =
                //    $vneseneIzgube[$mesec] - $this->potrebnaEnergija[$namen][$mesec];
            }
        }
    }

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
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

        $sistem->porociloNizi = [
            new TSSPorociloNiz(
                'E<sub>TČ</sub>',
                'Energija za delovanje TČ',
                $this->E_tc['ogrevanje'] ?? $this->E_tc,
                1
            ),
        ];

        $sistem->porociloPodatki = [
            new TSSPorociloPodatek('', 'Podnebje', $this->podnebje, '-'),
            new TSSPorociloPodatek(
                'P<sub>prim,aux</sub>',
                'El. moč na primarnem krogu',
                $this->elektricnaMocNaPrimarnemKrogu,
                'W'
            ),
            new TSSPorociloPodatek(
                'P<sub>sek,aux</sub>',
                'El. moč na sekundarnem krogu',
                $this->elektricnaMocNaSekundarnemKrogu,
                'W'
            ),
        ];

        return $sistem;
    }
}
