<?php
declare(strict_types=1);

namespace App\Calc\TSS\KoncniPrenosniki\Izbire;

enum VrstaHidravlicnegaUravnotezenja: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Neuravnotezeno = 'neuravnotezeno';
    case StaticnoUravnotezenjeKoncnihPrenosnikov = 'staticnoKoncnihPrenosnikov';
    case StaticnoUravnotezenjeDviznihVodov = 'staticnoDviznihVodov';
    case DinamicnoUravnotezenjePolnaObremenitev = 'dinamicnoPolnaObremenitev';
    case DinamicnoUravnotezenjeDelnaObremenitev = 'dinamicnoDelnaObremenitev';
}
