<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Izbire;

enum VrstaHidravlicnegaUravnotezenja: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Neuravnotezeno = 'neuravnotezeno';
    case StaticnoUravnotezenjeKoncnihPrenosnikov = 'staticnoKoncnihPrenosnikov';
    case StaticnoUravnotezenjeDviznihVodov = 'staticnoDviznihVodov';
    case DinamicnoUravnotezenjePolnaObremenitev = 'dinamicnoPolnaObremenitev';
    case DinamicnoUravnotezenjeDelnaObremenitev = 'dinamicnoDelnaObremenitev';

    /**
     * Friendly name
     *
     * @return string
     */
    public function toString()
    {
        $lookup = [
            'Neuravnoteženo',
            'Statično uravnotezenje končnih prenosnikov',
            'Statično uravnoteženje dvižnih vodov',
            'Dinamično uravnoteženje pri polni obremenitvi',
            'Dinamično uravnoteženje pri delni obremenitvi',
        ];

        return $lookup[$this->getOrdinal()];
    }
}
