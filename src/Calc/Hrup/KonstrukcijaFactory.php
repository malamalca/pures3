<?php
declare(strict_types=1);

namespace App\Calc\Hrup;

use App\Calc\Hrup\Elementi\EnostavnaKonstrukcija;
use App\Calc\Hrup\Elementi\Konstrukcija;

class KonstrukcijaFactory
{
    /**
     * Ustvari konstrukcijo glede na podani tip
     *
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\Hrup\Elementi\EnostavnaKonstrukcija|\App\Calc\Hrup\Elementi\Konstrukcija|null
     */
    public static function create($options)
    {
        if (empty($options->tip)) {
            return new EnostavnaKonstrukcija($options);
        }

        switch ($options->tip) {
            case 'zahtevna':
                return new Konstrukcija($options);
            case 'enostavna':
                return new EnostavnaKonstrukcija($options);
            default:
                throw new \Exception('Vrsta stavbe ne obstaja.');
        }
    }
}
