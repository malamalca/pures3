<?php

namespace App\Calc\TSS;

use App\Calc\TSS\KoncniPrenosniki\Konvektor;
use App\Calc\TSS\KoncniPrenosniki\PloskovnoOgrevalo;
use App\Calc\TSS\KoncniPrenosniki\Radiator;

class KoncniPrenosnikFactory
{
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