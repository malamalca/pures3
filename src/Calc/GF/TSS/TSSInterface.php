<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS;

use App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem;

abstract class TSSInterface
{
    public string $id;
    public array $vneseneIzgube = [];
    public array $potrebnaEnergija = [];
    public array $potrebnaElektricnaEnergija = [];
    public array $obnovljivaEnergija = [];
    public array $vracljiveIzgube = [];
    public array $vracljiveIzgubeAux = [];

    /**
     * Analiza podsistema
     *
     * @param array $potrebnaEnergija Potrebna energija predhodnih TSS
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    abstract public function analiza($potrebnaEnergija, OgrevalniSistem $sistem, $cona, $okolje, $params = []);

    /**
     * Export v json
     *
     * @return \stdClass
     */
    abstract public function export();
}
