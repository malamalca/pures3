<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS;

use App\Calc\GF\TSS\OgrevalniSistemi\LokalniOgrevalniSistemNaBiomaso;
use App\Calc\GF\TSS\OgrevalniSistemi\ToplovodniOgrevalniSistem;

class SistemOgrevanjaFactory
{
    /**
     * Ustvari ustrezen ogrevalni sistem glede na podan tip
     *
     * @param string $type Tip sistema
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem|null
     */
    public static function create($type, $options)
    {
        if ($type == 'toplovodni') {
            return new ToplovodniOgrevalniSistem($options);
        }
        if ($type == 'lokalniBiomasa') {
            return new LokalniOgrevalniSistemNaBiomaso($options);
        }

        return null;
    }
}
