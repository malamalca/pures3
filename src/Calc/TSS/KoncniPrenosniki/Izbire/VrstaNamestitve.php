<?php
declare(strict_types=1);

namespace App\Calc\TSS\KoncniPrenosniki\Izbire;

enum VrstaNamestitve: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case ObNotranjiSteni = 'notranjeStene';
    case ObZunanjemZidu = 'zunanjeStene';
    case ObZunanjemZiduZasteklitevBrezSevalneZascite = 'zasteklitevBrezZascite';
    case ObZunanjemZiduZasteklitevSSevalnoZascite = 'zasteklitevZZascito';
}
