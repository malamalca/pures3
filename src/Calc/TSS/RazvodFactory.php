<?php

namespace App\Calc\TSS;

use App\Calc\TSS\Razvodi\Dvocevni;
use App\Calc\TSS\Razvodi\Enocevni;


class RazvodFactory
{
    public static function create($type, $options = null)
    {
        switch ($type) {
            case 'dvocevni':
                return new Dvocevni($options);
            case 'enocevni':
                return new Enocevni($options);
            default:
                throw new \Exception(sprintf('Razvod : Vrsta "%s" ne obstaja', $type));
        }
    }
}