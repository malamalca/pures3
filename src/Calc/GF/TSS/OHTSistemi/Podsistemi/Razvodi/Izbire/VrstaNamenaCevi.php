<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire;

enum VrstaNamenaCevi: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Ogrevanje = 'ogrevanje';
    case ToplaSanitarnaVoda = 'TSV';
    case Hlajenje = 'hlajenje';
}
