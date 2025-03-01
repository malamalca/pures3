<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\ElektricnoOgrevalo;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Konvektor;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\PecNaDrva;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\PloskovnoOgrevalo;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Radiator;

class KoncniPrenosnikFactory
{
    /**
     * Ustvari ustrezen končni prenosnik glede na podan tip
     *
     * @param string $type Tip razvoda
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\KoncniPrenosnik
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
            case 'pecNaDrva':
                return new PecNaDrva($options);
            case 'hladilniStenskiKonvektor':
                return new \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Hlajenje\StenskiKonvektor($options);
            case 'hladilniStropniKonvektor':
                return new \App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki\Hlajenje\StropniKonvektor($options);
            default:
                throw new \Exception(sprintf('Končni prenosniki : Vrsta "%s" ne obstaja', $type));
        }
    }
}
