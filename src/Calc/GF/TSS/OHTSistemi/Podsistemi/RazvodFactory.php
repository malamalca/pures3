<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\DvocevniRazvod;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\EnocevniRazvod;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\RazvodHlajenja;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\RazvodSolar;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\RazvodTSV;

class RazvodFactory
{
    /**
     * Ustvari ustrezen razvod glede na podan tip
     *
     * @param string $type Tip razvoda
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Razvod
     */
    public static function create($type, $options = null)
    {
        switch ($type) {
            case 'dvocevni':
                return new DvocevniRazvod($options);
            case 'enocevni':
                return new EnocevniRazvod($options);
            case 'toplavoda':
                return new RazvodTSV($options);
            case 'hlajenje':
                return new RazvodHlajenja($options);
            case 'solar':
                return new RazvodSolar($options);
            default:
                throw new \Exception(sprintf('Razvod : Vrsta "%s" ne obstaja', $type));
        }
    }
}
