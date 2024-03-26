<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS;

use App\Calc\GF\TSS\OgrevalniSistemi\LokalniOHTSistemNaBiomaso;
use App\Calc\GF\TSS\OgrevalniSistemi\NeposredniElektricniOHTSistem;
use App\Calc\GF\TSS\OgrevalniSistemi\ToplovodniOHTSistem;

class SistemOgrevanjaFactory
{
    /**
     * Ustvari ustrezen ogrevalni sistem glede na podan tip
     *
     * @param string $type Tip sistema
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\GF\TSS\OgrevalniSistemi\OHTSistem|null
     */
    public static function create($type, $options)
    {
        if ($type == 'toplovodni') {
            return new ToplovodniOHTSistem($options);
        }
        if ($type == 'lokalniBiomasa') {
            return new LokalniOHTSistemNaBiomaso($options);
        }
        if ($type == 'neposrednoElektricni') {
            return new NeposredniElektricniOHTSistem($options);
        }

        return null;
    }
}
