<?php
declare(strict_types=1);

namespace App\Calc\TSS\KoncniPrenosniki\Izbire;

enum VrstaRegulacijeTemperature: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case CentralnaRegulacija = 'centralna';
    case ReferencniProstor = 'referencniProstor';
    case P_krmilnik = 'P-krmilnik';
    case PI_krmilnik = 'PI-krmilnik';
    case PI_krmilnikZOptimizacijo = 'PI-krmilnikZOptimizacijo';
}
