<?php
declare(strict_types=1);

namespace App\Calc;

use App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem;

interface TSSInterface
{
    /**
     * Toplotne izgube TSS
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \StdClass $cona Podatki cone
     * @param \StdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function toplotneIzgube($vneseneIzgube, OgrevalniSistem $sistem, $cona, $okolje, $params = []);

    /**
     * Potrebna električna energija za pomožne sisteme TSS
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \StdClass $cona Podatki cone
     * @param \StdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function potrebnaElektricnaEnergija($vneseneIzgube, OgrevalniSistem $sistem, $cona, $okolje, $params = []);

    /**
     * Vnesena obnovljiva energija iz okolja
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \StdClass $cona Podatki cone
     * @param \StdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function obnovljivaEnergija($vneseneIzgube, OgrevalniSistem $sistem, $cona, $okolje, $params = []);
}
