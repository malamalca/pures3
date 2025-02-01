<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire;

enum VrstaHlajenjaHladilnegaSistema: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case ZracnoHlajen = 'zracno';
    case VodnoHlajen = 'vodno';

    /**
     * Friendly name
     *
     * @return string
     */
    public function toString()
    {
        $lookup = [
            'Zračno hlajen sistem - hlajenje z ohlajeno vodo',
            'Vodno hlajen sistem - hlajenje z ohlajeno vodo',
        ];

        return $lookup[$this->getOrdinal()];
    }

    /**
     * ϑC,gen,hr,req,in,n
     *
     * @return float
     */
    public function TzaHlajenjeKondenzatorja()
    {
        return 35.0;
    }

    /**
     * ϑC,gen,req,out,n
     *
     * @return float
     */
    public function TnaIzstopuIzUparjalnika()
    {
        return 7.0;
    }

    /**
     * Δϑcond
     *
     * @param bool $kondenzatorVKanalu Kondenzator v kanalu odvodnega zraka (drugače je zunaj)
     * @return float
     */
    public function deltaTnaKondenzatorju(bool $kondenzatorVKanalu = false)
    {
        $ret = [$kondenzatorVKanalu ? 20.0 : 10.0, 4.0];

        return $ret[$this->getOrdinal()];
    }

    /**
     * Δϑevap
     *
     * @return float
     */
    public function deltaTnaUparjalniku()
    {
        return 6.0;
    }
}
