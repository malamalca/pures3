<?php
declare(strict_types=1);

namespace App\Calc\TSS;

use App\Calc\TSS\Razvodi\Dvocevni;
use App\Calc\TSS\Razvodi\Enocevni;

class RazvodFactory
{
    /**
     * Ustvari ustrezen razvod glede na podan tip
     *
     * @param string $type Tip razvoda
     * @param array|\StdClass|null $options Dodatne nastavitve
     * @return \App\Calc\TSS\Razvodi\Razvod
     */
    public static function create($type, $options = null)
    {
        switch ($type) {
            case 'dvocevni':
                return new Dvocevni($options);
            case 'enocevni':
                return new Enocevni($options);
            default:
                throw new \Exception(sprintf('Razvod : Vrsta "%s" ne obstaja', $type));
        }
    }
}
