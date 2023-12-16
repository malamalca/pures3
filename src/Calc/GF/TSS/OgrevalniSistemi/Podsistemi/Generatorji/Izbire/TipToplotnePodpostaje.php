<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Izbire;

enum TipToplotnePodpostaje: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Toplovod = 'toplovod';
    case Vrocevod = 'vrocevod';
    case NizkotlacniParovod = 'nizkotlacniParovod';
    case VisokotlacniParovod = 'visokotlacniParovod';

    /**
     * Koeficient BDS v odvisnosti od razreda toplotne izolacije toplotne podpostaje
     * Tabela 66
     *
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Izbire\VrstaRazredaIzolacije $vrstaRazreda Vrsta
     * @return float
     */
    public function faktorBds(VrstaRazredaIzolacije $vrstaRazreda)
    {
        $f = [
            VrstaRazredaIzolacije::Primarna4Sekundarna5->getOrdinal() => [3.5, 3.1, 2.8, 2.6],
            VrstaRazredaIzolacije::Primarna3Sekundarna4->getOrdinal() => [4.0, 3.5, 3.2, 3.0],
            VrstaRazredaIzolacije::Primarna2Sekundarna3->getOrdinal() => [4.4, 3.9, 3.5, 3.3],
            VrstaRazredaIzolacije::Primarna1Sekundarna2->getOrdinal() => [4.0, 4.3, 3.9, 3.7],
        ];

        return $f[$vrstaRazreda->getOrdinal()][$this->getOrdinal()];
    }

    /**
     * Dds v odvisnosti od vrste sistema daljinskega ogrevanja in projektne temperature
     * Tablea 65
     *
     * @return float
     */
    public function faktorDds()
    {
        $f = [0.6, 0.4, 0.5, 0.4];

        return $f[$this->getOrdinal()];
    }

    /**
     * Povprečna temperatura na primarni strani [°C]
     * Tablea 65
     *
     * @return float
     */
    public function temperaturaPrimarnegaMedija()
    {
        $f = [105, 150, 110, 180];

        return $f[$this->getOrdinal()];
    }
}
