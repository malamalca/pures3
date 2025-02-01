<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire;

enum VrstaRegulacijeHladilnegaKompresorja: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case OnOff = 'onOff';
    case Prilagodljivo = 'prilagodljivo';
    case Vecstopenjsko = 'vecstopenjsko';

    /**
     * Friendly name
     *
     * @return string
     */
    public function toString()
    {
        $lookup = [
            'ON/OFF Vklop',
            'Prilagodljivo delovanje',
            'Večstopenjsko delovanje',
        ];

        return $lookup[$this->getOrdinal()];
    }
}
