<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi\Podsistemi\Razvodi\Izbire;

enum RazvodAbstractProperties
{
    // Lmax - maksimalna dolžina razvoda glede na podatke cone
    case Lmax;
    // f_sch - korekcijski faktor za hidravlično omrežje [-]
    case f_sch;
}
