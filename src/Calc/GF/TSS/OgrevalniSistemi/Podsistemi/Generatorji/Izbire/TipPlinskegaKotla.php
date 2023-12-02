<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Izbire;

enum TipPlinskegaKotla: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case StandardniVentilatorski = 'standardniVentilatorski';
    case StandardniAtmosferski = 'standardniAtmosferski';
    case StandardniAtmosferskiNad250kW = 'standardniAtmosferskiNad250kW';
    case NizkotemperaturniSpecialniAtmosferski = 'nizkotemperaturniSpecialniAtmosferski';
    case NizkotemperaturniVentilatorski = 'nizkotemperaturniVentilatorski';
    case Kondenzacijski = 'kondenzacijski';

    /**
     * Friendly name
     *
     * @return string
     */
    public function toString()
    {
        $lookup = [
            'Standardni kotel z ventilatorskim gorilnikom',
            'Standardni kotel z atmosferskim gorilnikom',
            'Standardni kotel z atmosferskim gorilnikom nad 250 kW',
            'Nizkotemperaturni specialni kotel z atmosferskim gorilnikom',
            'Nizkotemperaturni kotel z ventilatorskim gorilnikom',
            'Kondenzacijski kotel',
        ];

        return $lookup[$this->getOrdinal()];
    }

    /**
     * Temperaturna omejitev T_h,g,min [°C]
     * Tabela 13
     *
     * @return float
     */
    public function temperaturnaOmejitev()
    {
        $t = [45, 45, 45, 35, 35, 20];

        return $t[$this->getOrdinal()];
    }

    /**
     * Izkoristek pri 100%
     * Tabela 13
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
            self::StandardniVentilatorski,
            self::StandardniAtmosferski,
            self::StandardniAtmosferskiNad250kW => (84 + 2 * log10($nazivnaMoc)) / 100,

            self::NizkotemperaturniSpecialniAtmosferski,
            self::NizkotemperaturniVentilatorski => (87.5 + 1.5 * log10($nazivnaMoc)) / 100,

            // todo: v tabelah je (91 + ...); v excelu pa (94 + ...
            self::Kondenzacijski => (94 + 1 * log10($nazivnaMoc)) / 100,
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
        // todo: 0.002 velja za kondenzacijski kotel (plinasta g)
        // še en faktor je za kondenzacijski kotel (tekoča g) => 0.0004
        $f = [0, 0, 0, 0.0004, 0.0004, 0.002];

        return $f[$this->getOrdinal()];
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
            self::StandardniVentilatorski,
            self::StandardniAtmosferski,
            self::StandardniAtmosferskiNad250kW => (80 + 3 * log10($nazivnaMoc)) / 100,

            self::NizkotemperaturniSpecialniAtmosferski,
            self::NizkotemperaturniVentilatorski => (87.5 + 1.5 * log10($nazivnaMoc)) / 100,

            // todo: v tabelah je (97 + ...); v excelu pa (103 + ...
            self::Kondenzacijski => (103 + 1 * log10($nazivnaMoc)) / 100,
        };
    }

    /**
     * Povprečna temperatura kotla pri testnih pogojih / vmesni (30%) obremenitvi
     * Tabela 15
     *
     * @return float
     */
    public function temperaturaKotlaVmesneObremenitve()
    {
        $t = [50, 50, 50, 40, 40, 35];

        return $t[$this->getOrdinal()];
    }

    /**
     * Korekcijski faktor fcor,Pint pri 30% obremenitvi
     * Tabela 15
     *
     * @return float
     */
    public function korekcijskiFaktorIzkoristkaVmesneObremenitve()
    {
        // todo: 0.002 velja za kondenzacijski kotel (plinasta g)
        // še en faktor je za kondenzacijski kotel (tekoča g) => 0.001
        $f = [0.0004, 0.0004, 0.0004, 0.0004, 0.0004, 0.002];

        return $f[$this->getOrdinal()];
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
            self::StandardniVentilatorski,
            self::StandardniAtmosferski,
            self::StandardniAtmosferskiNad250kW => $nazivnaMoc * (25 - 8 * log10($nazivnaMoc)) / 1000,

            self::NizkotemperaturniSpecialniAtmosferski,
            self::NizkotemperaturniVentilatorski,
            self::Kondenzacijski => $nazivnaMoc * (17.5 - 5.5 * log10($nazivnaMoc)) / 1000,
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
            self::StandardniVentilatorski,
            self::StandardniAtmosferski,
            self::StandardniAtmosferskiNad250kW,
            self::NizkotemperaturniVentilatorski,
            self::Kondenzacijski => 0.75,

            self::NizkotemperaturniSpecialniAtmosferski => 0.5,
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
            self::StandardniAtmosferski,
            self::StandardniAtmosferskiNad250kW,
            self::NizkotemperaturniSpecialniAtmosferski =>
                ($lokacija == VrstaLokacijeNamestitve::OgrevanProstor ? 0.2 : 0.7),

            self::StandardniVentilatorski,
            self::NizkotemperaturniVentilatorski,
            self::Kondenzacijski => ($lokacija == VrstaLokacijeNamestitve::OgrevanProstor ? 0.1 : 0.7),
        };
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
                return match ($this) {
                    self::NizkotemperaturniSpecialniAtmosferski => (0.148 * $nazivnaMoc + 40) / 1000,
                    self::NizkotemperaturniVentilatorski => 0.045 * pow($nazivnaMoc, 0.48),
                    self::Kondenzacijski => 0.045 * pow($nazivnaMoc, 0.48),

                    self::StandardniVentilatorski => 0.045 * pow($nazivnaMoc, 0.48),

                    self::StandardniAtmosferski => (0.35 * $nazivnaMoc + 40) / 1000,
                    self::StandardniAtmosferskiNad250kW => (0.7 * $nazivnaMoc + 80) / 1000,
                };
            case 'vmesna':
                return match ($this) {
                    self::NizkotemperaturniSpecialniAtmosferski => (0.148 * $nazivnaMoc + 40) / 1000,
                    self::NizkotemperaturniVentilatorski => 0.015 * pow($nazivnaMoc, 0.48),
                    self::Kondenzacijski => 0.015 * pow($nazivnaMoc, 0.48),

                    self::StandardniVentilatorski => 0.015 * pow($nazivnaMoc, 0.48),

                    self::StandardniAtmosferski => (0.1 * $nazivnaMoc + 20) / 1000,
                    self::StandardniAtmosferskiNad250kW => (0.2 * $nazivnaMoc + 40) / 1000,
                };
            case 'min':
                return match ($this) {
                    self::StandardniVentilatorski,
                    self::NizkotemperaturniVentilatorski,
                    self::Kondenzacijski => 0.0,

                    self::StandardniAtmosferski,
                    self::NizkotemperaturniSpecialniAtmosferski => 0.015,

                    self::StandardniAtmosferskiNad250kW => 0.015
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
