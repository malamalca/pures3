<?php
declare(strict_types=1);

namespace App\Calc\TSS\Razvodi\Izbire;

enum VrstaNamenaCevi: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Ogrevanje = 'ogrevanje';
    case ToplaSanitarnaVoda = 'TSV';
    case Hlajenje = 'hlajenje';
}
