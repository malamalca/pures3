<?php
declare(strict_types=1);

namespace App\Calc\TSS;

use App\Calc\TSS\Energenti\Elektrika;

class EnergentFactory
{
    /**
     * Ustvari ustrezen energent glede na podan tip
     *
     * @param string $type Tip razvoda
     * @param \StdClass|string|null $options Dodatne nastavitve
     * @return \App\Calc\TSS\Energenti\Energent
     */
    public static function create($type, $options = null)
    {
        switch ($type) {
            case 'elektrika':
                return new Elektrika($options);
            default:
                throw new \Exception(sprintf('Energenti : Vrsta "%s" ne obstaja', $type));
        }
    }
}
