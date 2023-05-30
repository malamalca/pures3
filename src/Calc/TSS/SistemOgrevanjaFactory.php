<?php
declare(strict_types=1);

namespace App\Calc\TSS;

use App\Calc\TSS\OgrevalniSistemi\ToplovodniOgrevalniSistem;

class SistemOgrevanjaFactory
{
    /**
     * Ustvari ustrezen ogrevalni sistem glede na podan tip
     *
     * @param string $type Tip sistema
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem|null
     */
    public static function create($type, $options)
    {
        if ($type == 'toplovodni') {
            return new ToplovodniOgrevalniSistem($options);
        }

        return null;
    }
}
