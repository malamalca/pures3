<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Izbire;

enum VrstaIzolacijePloskovnihOgreval: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case BrezMinimalneIzolacije = 'brez';
    case MinimalnaIzolacija = 'min';
    case PovecanaIzolacija100Procentov = '100%';
}
