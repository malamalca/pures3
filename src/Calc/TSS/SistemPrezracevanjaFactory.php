<?php
declare(strict_types=1);

namespace App\Calc\TSS;

use App\Calc\TSS\PrezracevalniSistemi\CentralniPrezracevalniSistem;

class SistemPrezracevanjaFactory
{
    /**
     * Ustvari ustrezen ogrevalni sistem glede na podan tip
     *
     * @param string $type Tip sistema
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\TSS\PrezracevalniSistemi\PrezracevalniSistem|null
     */
    public static function create($type, $options)
    {
        switch ($type) {
            case 'centralni':
                return new CentralniPrezracevalniSistem($options);
            default:
                throw new \Exception('Vrsta prezračevalnega sistema ne obstaja.');
        }
    }
}
