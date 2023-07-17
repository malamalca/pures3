<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi\Podsistemi;

use App\Calc\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\ElektricnoOgrevalo;
use App\Calc\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\Konvektor;
use App\Calc\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\PloskovnoOgrevalo;
use App\Calc\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\Radiator;

class KoncniPrenosnikFactory
{
    /**
     * Ustvari ustrezen končni prenosnik glede na podan tip
     *
     * @param string $type Tip razvoda
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\KoncniPrenosnik
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
            case 'elektricnoOgrevalo':
                return new ElektricnoOgrevalo($options);
            default:
                throw new \Exception(sprintf('Končni prenosniki : Vrsta "%s" ne obstaja', $type));
        }
    }
}
