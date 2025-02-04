<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS;

use App\Calc\GF\TSS\OHTSistemi\HladilniSistemSHladnoVodo;
use App\Calc\GF\TSS\OHTSistemi\LokalniOHTSistemNaBiomaso;
use App\Calc\GF\TSS\OHTSistemi\NeposredniElektricniOHTSistem;
use App\Calc\GF\TSS\OHTSistemi\SplitHladilniOHTSistem;
//use App\Calc\GF\TSS\OHTSistemi\SevalniOHTSistem;
use App\Calc\GF\TSS\OHTSistemi\ToplovodniOHTSistem;
//use App\Calc\GF\TSS\OHTSistemi\ToplovodniOHTSistem;

class SistemOHTFactory
{
    /**
     * Ustvari ustrezen ogrevalni sistem glede na podan tip
     *
     * @param string $type Tip sistema
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\GF\TSS\OHTSistemi\OHTSistem|null
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
        if ($type == 'splitHlajenje') {
            return new SplitHladilniOHTSistem($options);
        }
        if ($type == 'hladilni') {
            return new HladilniSistemSHladnoVodo($options);
        }

        return null;
    }
}
