<?php

namespace App\Calc\TSS\OgrevalniSistemi;

use App\Calc\TSS\Energenti\Energent;

abstract class OgrevalniSistem {
    public Energent $energent;

    protected array $izgube;

    protected array $razvodi = [];
    protected array $koncniPrenosniki = [];

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