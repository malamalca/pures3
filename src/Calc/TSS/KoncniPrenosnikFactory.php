<?php

namespace App\Calc\TSS;

use App\Calc\TSS\KoncniPrenosniki\PloskovnoOgrevalo;


class KoncniPrenosnikFactory
{
    public static function create($type, $options)
    {
        switch ($type) {
            case 'ploskovnaOgrevala':
                return new PloskovnoOgrevalo($options);
            default:
                throw new \Exception(sprintf('Končni prenosniki : Vrsta "%s" ne obstaja', $type));
        }
    }
}