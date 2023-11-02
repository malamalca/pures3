<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZunanjiHrup\Izbire;

enum VrstaKazalcevHrupa: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case GledeNaObmocje = 'obmocje';
    case IzmerjeniAliIzracunani = 'lastni';
}
