<?php
declare(strict_types=1);

namespace App\Calc\TSS;

use App\Calc\TSS\KoncniPrenosniki\Konvektor;
use App\Calc\TSS\KoncniPrenosniki\PloskovnoOgrevalo;
use App\Calc\TSS\KoncniPrenosniki\Radiator;

class KoncniPrenosnikFactory
{
    /**
     * Ustvari ustrezen končni prenosnik glede na podan tip
     *
     * @param string $type Tip razvoda
     * @param \stdClass|string|null $options Dodatne nastavitve
     * @return \App\Calc\TSS\KoncniPrenosniki\KoncniPrenosnik
     */
    public static function create($type, $options)
    {
        switch ($type) {
            case 'radiatorji':
                return new Radiator($options);
            case 'konvektorji':
                return new Konvektor($options);
            case 'ploskovnaOgrevala':
                return new PloskovnoOgrevalo($options);
            default:
                throw new \Exception(sprintf('Končni prenosniki : Vrsta "%s" ne obstaja', $type));
        }
    }
}
