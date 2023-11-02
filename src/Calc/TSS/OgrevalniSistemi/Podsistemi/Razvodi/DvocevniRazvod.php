<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi\Podsistemi\Razvodi;

use App\Calc\TSS\OgrevalniSistemi\Podsistemi\Razvodi\Izbire\RazvodAbstractProperties;
use App\Calc\TSS\OgrevalniSistemi\Podsistemi\Razvodi\Izbire\VrstaRazvodnihCevi;

class DvocevniRazvod extends RazvodOgrevanje
{
    public string $sistem = 'dvocevni';

    /**
     * Vrne dolžino cevi za podano vrsto razvodnih cevi
     *
     * @param \App\Calc\TSS\OgrevalniSistemi\Podsistemi\Razvodi\Izbire\VrstaRazvodnihCevi $vrsta Vrsta razvodne cevi
     * @param \stdClass $cona Podatki cone
     * @return float
     */
    public function dolzinaCevi(VrstaRazvodnihCevi $vrsta, $cona)
    {
        switch ($vrsta) {
            case VrstaRazvodnihCevi::HorizontalniRazvod:
                return 2 * $cona->dolzina + 0.0325 * $cona->dolzina * $cona->sirina + 6;
            case VrstaRazvodnihCevi::DvizniVod:
                return 0.025 * $cona->dolzina * $cona->sirina * $cona->steviloEtaz * $cona->etaznaVisina;
            case VrstaRazvodnihCevi::PrikljucniVod:
                return 0.55 * $cona->dolzina * $cona->sirina * $cona->steviloEtaz;
        }
    }

    /**
     * Vrne zahtevano fiksno vrednost konstante/količine
     *
     * @param \App\Calc\TSS\OgrevalniSistemi\Podsistemi\Razvodi\Izbire\RazvodAbstractProperties $property Količina/konstanta
     * @param array $options Dodatni parametri
     * @return int|float
     */
    public function getProperty(RazvodAbstractProperties $property, $options = [])
    {
        switch ($property) {
            case RazvodAbstractProperties::Lmax:
                $cona = $options['cona'];
                $lc = 10;
                $Lmax = 2 * ($cona->dolzina + $cona->sirina / 2 + $cona->etaznaVisina * $cona->steviloEtaz + $lc);

                return $Lmax;
            case RazvodAbstractProperties::f_sch:
                return 1;
        }
    }
}
