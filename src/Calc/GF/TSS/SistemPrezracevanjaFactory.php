<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS;

use App\Calc\GF\TSS\PrezracevalniSistemi\CentralniPrezracevalniSistem;
use App\Calc\GF\TSS\PrezracevalniSistemi\LokalniPrezracevalniSistem;

class SistemPrezracevanjaFactory
{
    /**
     * Ustvari ustrezen ogrevalni sistem glede na podan tip
     *
     * @param string $type Tip sistema
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\GF\TSS\PrezracevalniSistemi\PrezracevalniSistem|null
     */
    public static function create($type, $options)
    {
        switch ($type) {
            case 'centralni':
                return new CentralniPrezracevalniSistem($options);
            case 'lokalni':
                return new LokalniPrezracevalniSistem($options);
            default:
                throw new \Exception('Vrsta prezračevalnega sistema ne obstaja.');
        }
    }
}
