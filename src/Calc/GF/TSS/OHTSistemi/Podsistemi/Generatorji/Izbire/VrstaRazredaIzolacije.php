<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire;

enum VrstaRazredaIzolacije: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Primarna4Sekundarna5 = 'primarna4sekundarna5';
    case Primarna3Sekundarna4 = 'primarna3sekundarna4';
    case Primarna2Sekundarna3 = 'primarna2sekundarna3';
    case Primarna1Sekundarna2 = 'primarna1sekundarna2';
}
