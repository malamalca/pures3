<?php
declare(strict_types=1);

namespace App\Calc\TSS;

use App\Calc\TSS\Razvodi\DvocevniRazvod;
use App\Calc\TSS\Razvodi\EnocevniRazvod;

class RazvodFactory
{
    /**
     * Ustvari ustrezen razvod glede na podan tip
     *
     * @param string $type Tip razvoda
     * @param array|\stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\TSS\Razvodi\Razvod
     */
    public static function create($type, $options = null)
    {
        switch ($type) {
            case 'dvocevni':
                return new DvocevniRazvod($options);
            case 'enocevni':
                return new EnocevniRazvod($options);
            default:
                throw new \Exception(sprintf('Razvod : Vrsta "%s" ne obstaja', $type));
        }
    }
}
