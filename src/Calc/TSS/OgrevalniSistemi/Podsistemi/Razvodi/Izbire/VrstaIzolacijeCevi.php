<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi\Podsistemi\Razvodi\Izbire;

enum VrstaIzolacijeCevi: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case IzoliraneCevi = 'izolirane';
    case NeizoliraneCeviVIzoliraniZunanjiSteni = 'neizoliraneZunaj';
    case NeizoliraneCeviVNeizoliraniZunanjiSteni = 'neizoliraneZnotraj';
    case NeizoliraneCeviVZraku = 'neizoliraneVZraku';
    case NeizoliraneCeviVNotranjiSteni = 'neizoliraneVNotranjiSteni';
}
