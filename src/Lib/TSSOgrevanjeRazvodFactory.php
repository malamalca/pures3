<?php

namespace App\Lib;

use App\Lib\Razvodi\Dvocevni;
use App\Lib\Razvodi\Enocevni;


class TSSOgrevanjeRazvodFactory
{
    public static function create($type, $options)
    {
        if ($type == 'dvocevni') {
            return new Dvocevni($options);
        }
        if ($type == 'enocevni') {
            return new Enocevni($options);
        }
    }
}