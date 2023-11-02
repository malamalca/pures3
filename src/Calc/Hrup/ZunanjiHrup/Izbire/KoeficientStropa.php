<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZunanjiHrup\Izbire;

enum KoeficientStropa: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case ManjKot03 = '<=0.3';
    case Enako06 = '=0.6';
    case VecKot09 = '>=0.9';
}
