<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire;

enum VrstaHladilnegaKompresorja: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Batni = 'batni';
    case Spiralni = 'spiralni';
    case Vijacni = 'vijacni';
    case Turbinski = 'turbinski';

    /**
     * Friendly name
     *
     * @return string
     */
    public function toString()
    {
        $lookup = [
            'Batni kompresor',
            'Spiralni kompresor',
            'Vijačni kompresor',
            'Turbinski kompresor',
        ];

        return $lookup[$this->getOrdinal()];
    }

    /**
     * a0, a1, a2
     *
     * @param \App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire\VrstaHlajenjaHladilnegaSistema $vrstaSistema Vrsta sistema
     * @return array
     */
    public function faktorA(VrstaHlajenjaHladilnegaSistema $vrstaSistema)
    {
        $ret = [
            // zračno hlajen
            0 => [
                0 => [2.64, -0.07753, 0.00083],
                1 => [2.64, -0.07753, 0.00083],
                2 => [2.91, -0.08224, 0.00071],
                3 => [2.91, -0.08224, 0.00071],
            ],
            // vodno hlajen
        ];

        return $ret[$vrstaSistema->getOrdinal()][$this->getOrdinal()];
    }
}
