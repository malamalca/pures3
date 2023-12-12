<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Izbire;

enum TipBiomasa: string
{
    //use \App\Lib\Traits\GetOrdinalTrait;

    case StandardniZAvtomatskimDodajanjemGoriva = 'standardniZAvtomatskimDodajanjemGoriva';

    /**
     * Friendly name
     *
     * @return string
     */
    public function toString()
    {
        $lookup = [
            'Standardni kotel z avtomatskim dodajanjem goriva',
        ];

        return $lookup[0];
    }

    /**
     * Temperaturna omejitev T_h,g,min [°C]
     * Tabela 13
     *
     * @return float
     */
    public function temperaturnaOmejitev()
    {
        $t = [45];

        return $t[0];
    }

    /**
     * Izkoristek pri 100%, ni_h_g_Pn
     * Tabela 13 ali AH254 v Excelu
     *
     * @param float $nazivnaMoc Nazivna moč kotla v kW
     * @return float
     */
    public function izkoristekPolneObremenitve($nazivnaMoc)
    {
        if ($nazivnaMoc > 400) {
            $nazivnaMoc = 400;
        }

        return match ($this) {
            self::StandardniZAvtomatskimDodajanjemGoriva => (84 + 2 * log10($nazivnaMoc)) / 100,
        };
    }

    /**
     * Izkoristek pri 30%
     * Tabela 13
     *
     * @param float $nazivnaMoc Nazivna moč kotla v kW
     * @return float
     */
    public function izkoristekVmesneObremenitve($nazivnaMoc)
    {
        if ($nazivnaMoc > 400) {
            $nazivnaMoc = 400;
        }

        return match ($this) {
            self::StandardniZAvtomatskimDodajanjemGoriva => (80 + 3 * log10($nazivnaMoc)) / 100,
        };
    }

    /**
     * povprečna temp. kotla pri testnih pogojih (100% obremenitvi) [°C] (Tabela 14)
     * iz tabele je 70 °C za vse tipe kotlov
     *
     * @return float
     */
    public function temperaturaKotlaPolneObremenitve()
    {
        return 70;
    }

    /**
     * Korekcijski faktor fcor,Pn pri 100% obremenitvi
     * Tabela 14
     *
     * @return float
     */
    public function korekcijskiFaktorIzkoristkaPolneObremenitve()
    {
        $f = [0];

        return $f[0];
    }

    /**
     * Povprečna temperatura kotla pri testnih pogojih / vmesni (30%) obremenitvi
     * Tabela 15 ali AL256
     *
     * @return float
     */
    public function temperaturaKotlaVmesneObremenitve()
    {
        $t = [70];

        return $t[0];
    }

    /**
     * Korekcijski faktor fcor,Pint pri 30% obremenitvi
     * Tabela 15
     *
     * @return float
     */
    public function korekcijskiFaktorIzkoristkaVmesneObremenitve()
    {
        $f = [0.0004];

        return $f[0];
    }

    /**
     * Toplotne izgube kotla v času obratovalne pripravljenosti Qh,g,l,P0 [kW]
     * Tabela 16
     *
     * @param float $nazivnaMoc Nazivna moč kotla v kW
     * @return float
     */
    public function izgubeStandBy($nazivnaMoc)
    {
        return match ($this) {
            self::StandardniZAvtomatskimDodajanjemGoriva => $nazivnaMoc * (25 - 8 * log10($nazivnaMoc)) / 1000,
        };
    }

    /**
     * Specifične toplotne izgube kotla qw,g,70 [-] v odvisnosti od vrsta kotla in nazivne moči w g Pn Q , ,& [kW]
     * Tabela 24
     *
     * @param float $nazivnaMoc Nazivna moč kotla v kW
     * @return float
     */
    public function izgube70($nazivnaMoc)
    {
        return match ($this) {
            self::StandardniZAvtomatskimDodajanjemGoriva => 14 * pow($nazivnaMoc, -0.28) / 100,
        };
    }

    /**
     * Del toplotnih izgub skozi ovoj kotla v času obratovalne pripravljenosti pgn,env
     * Tabela 18
     *
     * @return float
     */
    public function faktorIzgubSkoziOvoj()
    {
        return match ($this) {
            self::StandardniZAvtomatskimDodajanjemGoriva => 0.75,
        };
    }

    /**
     * delež vrnjenih toplotnih izgub skozi ovoj kotla [-]
     *
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Izbire\VrstaLokacijeNamestitve $lokacija Lokacija namestitve
     * @return float
     */
    public function delezVrnjenihIzgubSkoziOvoj(VrstaLokacijeNamestitve $lokacija)
    {
        if ($lokacija == VrstaLokacijeNamestitve::NeogrevanProstor) {
            return 1;
        }

        return match ($this) {
            self::StandardniZAvtomatskimDodajanjemGoriva =>
                ($lokacija == VrstaLokacijeNamestitve::OgrevanProstor ? 0.1 : 0.7),
        };
    }

    /**
     * Obremenitev kotla pri testnih pogojih za vmesno obremenitev β_h, g, test, Pint
     * Za enačbo 98; Excel AN254
     *
     * @return float
     */
    public function vmesnaObremenitev()
    {
        return 0.4;
    }

    /**
     * Tabela 17: Moč pomožnih električnih naprav Paux,g [kW]
     *
     * @param float $nazivnaMoc Nazivna moč kotla
     * @param string $obremenitev Obremenitev kotla
     * @return float
     */
    public function mocPomoznihElektricnihNaprav($nazivnaMoc, $obremenitev)
    {
        switch ($obremenitev) {
            case 'polna':
                // AI254
                return match ($this) {
                    self::StandardniZAvtomatskimDodajanjemGoriva => (2.6 * $nazivnaMoc + 60) / 1000,
                };
            case 'vmesna':
                // AI256
                return match ($this) {
                    self::StandardniZAvtomatskimDodajanjemGoriva => (2.2 * $nazivnaMoc + 70) / 1000,
                };
            case 'min':
                return match ($this) {
                    self::StandardniZAvtomatskimDodajanjemGoriva => 0.015,
                };
            default:
                return 0.0;
        }
    }

    /**
     * Vrnjena dodatna električna energija - faktor redukcije ki upošteva vpliv okolice.
     *
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Izbire\VrstaLokacijeNamestitve $lokacija Lokacija namestitve
     * @return float
     */
    public function faktorRedukcijeVrnjeneEnergije(VrstaLokacijeNamestitve $lokacija)
    {
        if ($lokacija == VrstaLokacijeNamestitve::OgrevanProstor) {
            return 0;
        }

        if ($lokacija == VrstaLokacijeNamestitve::Kotlovnica) {
            return 0.3;
        }

        return 0.00;
    }
}
