<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi\Podsistemi\Razvodi\Izbire;

enum VrstaRazvodnihCevi: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case HorizontalniRazvod = 'ceviHorizontaliVodi';
    case DvizniVod = 'ceviDvizniVodi';
    case PrikljucniVod = 'ceviPrikljucniVodi';
}
