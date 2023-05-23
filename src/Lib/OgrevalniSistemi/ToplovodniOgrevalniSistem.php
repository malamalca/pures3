<?php

namespace App\Calc\OgrevalniSistemi;

class ToplovodniOgrevalniSistem extends TSSOgrevanjeOgrevalniSistem {
    public \App\TSSOgrevanjeEvergent $energent;

    public function __construct($json = null)
    {
        if ($json) {
            $this->parseJson($json);
        }
    }

    abstract protected function parseJson(string $json);
}

?>