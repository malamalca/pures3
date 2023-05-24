<?php

namespace App\Calc\TSS\Razvodi;


class DvocevniZunaj extends Razvod
{
    public function dolzinaCevi(VrstaRazvodnihCevi $vrsta, $cona)
    {
        switch ($vrsta) {
            case VrstaRazvodnihCevi::HorizontalniRazvod:
                return 2 * $cona->dolzina + 0.01625 * $cona->dolzina * pow($cona->sirina, 2);
                break;
            case VrstaRazvodnihCevi::DvizniVodi:
                return 0.025 * $cona->dolzina * $cona->sirina * $cona->steviloEtaz * $cona->etaznaVisina;
                break;
            case VrstaRazvodnihCevi::PrikljucniVodi:
                return 0.55 * $cona->dolzina * $cona->sirina * $cona->steviloEtaz;
                break;
        }
    }

    public function maksimalnaDolzinaCevi($cona)
    {
        // lc = 10 za dvocevni sistem 
        $lc = 10;

        $Lmax = 2 * ($cona->dolzina + $cona->sirina / 2 + $cona->etaznaVisina * $cona->steviloEtaz + $lc);
    }
}