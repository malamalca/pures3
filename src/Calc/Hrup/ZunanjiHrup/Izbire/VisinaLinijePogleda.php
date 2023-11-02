<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZunanjiHrup\Izbire;

enum VisinaLinijePogleda: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case ManjKot15 = '<1.5m';
    case Od15Do25 = '1.5-2.5m';
    case VecKot25 = '>2.5m';
}
