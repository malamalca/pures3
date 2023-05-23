<?php

namespace App\Calc\TSS;

use App\Calc\TSS\Energenti\Elektrika;

class EnergentFactory
{
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