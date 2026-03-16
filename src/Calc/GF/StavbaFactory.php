<?php
declare(strict_types=1);

namespace App\Calc\GF;

use App\Calc\GF\Stavbe\ManjzahtevnaStavba;
use App\Calc\GF\Stavbe\NezahtevnaStavba;
use App\Calc\GF\Stavbe\ZahtevnaStavba;

class StavbaFactory
{
    /**
     * Ustvari stavbo glede na podan tip
     *
     * @param string $type Tip sistema
     * @param \stdClass|null $options Dodatne nastavitve
     * @param int $year Leto za izracune
     * @return \App\Calc\GF\Stavbe\Stavba|null
     */
    public static function create($type, $options, $year)
    {
        switch ($type) {
            case 'nezahtevna':
                return new NezahtevnaStavba($options, $year);
            case 'manjzahtevna':
                return new ManjzahtevnaStavba($options, $year);
            case 'zahtevna':
                return new ZahtevnaStavba($options, $year);
            default:
                throw new \Exception('Vrsta stavbe ne obstaja.');
        }
    }
}
