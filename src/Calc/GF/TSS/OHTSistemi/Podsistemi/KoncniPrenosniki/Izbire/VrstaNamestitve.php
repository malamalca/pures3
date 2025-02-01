<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Izbire;

enum VrstaNamestitve: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case ObNotranjiSteni = 'notranjeStene';
    case ObZunanjemZidu = 'zunanjeStene';
    case ObZunanjemZiduZasteklitevBrezSevalneZascite = 'zasteklitevBrezZascite';
    case ObZunanjemZiduZasteklitevSSevalnoZascite = 'zasteklitevZZascito';

    /**
     * Friendly name
     *
     * @return string
     */
    public function toString()
    {
        $lookup = [
            'Ob notranji steni',
            'Ob zunanjem zidu',
            'Ob zunanjem zidu - zasteklitev brez sevalne zaščite',
            'Ob zunanjem zidu - zasteklitev s sevalno zaščito',
        ];

        return $lookup[$this->getOrdinal()];
    }
}
