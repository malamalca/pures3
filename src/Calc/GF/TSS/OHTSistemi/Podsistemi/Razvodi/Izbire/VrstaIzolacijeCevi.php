<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire;

enum VrstaIzolacijeCevi: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case IzoliraneCevi = 'izolirane';
    case NeizoliraneCeviVIzoliraniZunanjiSteni = 'neizoliraneZunaj';
    case NeizoliraneCeviVNeizoliraniZunanjiSteni = 'neizoliraneZnotraj';
    case NeizoliraneCeviVZraku = 'neizoliraneVZraku';
    case NeizoliraneCeviVNotranjiSteni = 'neizoliraneVNotranjiSteni';
}
