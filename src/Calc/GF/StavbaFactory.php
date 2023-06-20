<?php
declare(strict_types=1);

namespace App\Calc\GF;

use App\Calc\GF\Stavbe\ManjzahtevnaStavba;

class StavbaFactory
{
    /**
     * Ustvari stavbo glede na podan tip
     *
     * @param string $type Tip sistema
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\GF\Stavbe\Stavba|null
     */
    public static function create($type, $options)
    {
        switch ($type) {
            case 'manjzahtevna':
                return new ManjzahtevnaStavba($options);
            default:
                throw new \Exception('Vrsta stavbe ne obstaja.');
        }
    }
}
