<?php

namespace App\Calc\TSS;

use App\Calc\TSS\OgrevalniSistemi\ToplovodniOgrevalniSistem;

class SistemOgrevanjaFactory
{
    public static function create($type, $options)
    {
        if ($type == 'toplovodni') {
            return new ToplovodniOgrevalniSistem($options);
        }
    }
}