<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Hlajenje;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Konvektor;

class StropniKonvektor extends Konvektor
{
    public float $deltaT_str = 0.0;
    public float $deltaT_emb = 0.0;
    public float $deltaT_im = -0.3;
    public float $deltaT_sol = 12.0;
}
