<?php

namespace App\Lib;

use App\Lib\TSSOgrevanjeEnergent;

abstract class TSSOgrevanjeOgrevalniSistem {
    public TSSOgrevanjeEnergent $energent;

    protected array $izgube;

    protected array $razvodi;

    public function __construct($config = null)
    {
        if ($config) {
            $this->parseConfig($config);
        }
    }

    abstract protected function parseConfig($config);

    abstract public function analiza($cona, $okolje);
}

?>