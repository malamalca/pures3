<?php

namespace App\Lib;

use App\Lib\OgrevalniSistemi\ToplovodniOgrevalniSistem;

class TSSOgrevanjeSistemOgrevanjaFactory
{
    public static function create($type, $options)
    {
        if ($type == 'toplovodni') {
            return new ToplovodniOgrevalniSistem($options);
        }
    }
}