<?php
declare(strict_types=1);

namespace App\Calc\TSS\Razvodi;

use App\Calc\TSS\Razvodi\Izbire\RazvodAbstractProperties;
use App\Calc\TSS\Razvodi\Izbire\VrstaRazvodnihCevi;

class Enocevni extends Razvod
{
    /**
     * Vrne dolžino cevi za podano vrsto razvodnih cevi
     *
     * @param \App\Calc\TSS\Razvodi\Izbire\VrstaRazvodnihCevi $vrsta Vrsta razvodne cevi
     * @param \stdClass $cona Podatki cone
     * @return float
     */
    public function dolzinaCevi(VrstaRazvodnihCevi $vrsta, $cona)
    {
        switch ($vrsta) {
            case VrstaRazvodnihCevi::HorizontalniRazvod:
                return 2 * $cona->dolzina + 0.01625 * $cona->dolzina * pow($cona->sirina, 2);
            case VrstaRazvodnihCevi::DvizniVod:
                return 0.025 * $cona->dolzina * $cona->sirina * $cona->steviloEtaz * $cona->etaznaVisina +
                    2 * ($cona->dolzina + $cona->sirina) * $cona->steviloEtaz;
            case VrstaRazvodnihCevi::PrikljucniVod:
                return 0.1 * $cona->dolzina * $cona->sirina * $cona->steviloEtaz;
        }

        return 0;
    }

    /**
     * Vrne zahtevano fiksno vrednost konstante/količine
     *
     * @param \App\Calc\TSS\Razvodi\Izbire\RazvodAbstractProperties $property Količina/konstanta
     * @param array $options Dodatni parametri
     * @return int|float
     */
    public function getProperty(RazvodAbstractProperties $property, $options = [])
    {
        switch ($property) {
            case RazvodAbstractProperties::Lmax:
                $cona = $options['cona'];
                $lc = $cona->dolzina + $cona->sirina;

                $Lmax = 2 * ($cona->dolzina + $cona->sirina / 2 + $cona->etaznaVisina * $cona->steviloEtaz + $lc);

                return $Lmax;
            case RazvodAbstractProperties::f_sch:
                return 2.3;
        }

        return 0;
    }
}
