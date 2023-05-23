<?php

namespace App\Lib;

use App\Lib\Energenti\Elektrika;

class TSSOgrevanjeEnergentFactory
{
    public static function create($type)
    {
        if ($type == 'elektrika') {
            return new Elektrika();
        }
    }
}