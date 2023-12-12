<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS;

use App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem;

abstract class TSSInterface
{
    public ?string $id;

    public array $toplotneIzgube = [];
    public array $potrebnaElektricnaEnergija = [];
    public array $obnovljivaEnergija = [];

    public array $vracljiveIzgube = [];
    public array $vracljiveIzgubeTSV = [];
    public array $vracljiveIzgubeAux = [];

    /**
     * Analiza podsistema
     *
     * @param array $toplotneIzgube Potrebna energija predhodnih TSS
     * @param \App\Calc\GF\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    abstract public function analiza($toplotneIzgube, OgrevalniSistem $sistem, $cona, $okolje, $params = []);

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $sistem = new \stdClass();
        $sistem->id = $this->id;

        $sistem->toplotneIzgube = $this->toplotneIzgube;
        $sistem->potrebnaElektricnaEnergija = $this->potrebnaElektricnaEnergija;
        $sistem->obnovljivaEnergija = $this->obnovljivaEnergija;

        $sistem->toplotneIzgube = $this->toplotneIzgube;

        $sistem->vracljiveIzgube = $this->vracljiveIzgube;
        $sistem->vracljiveIzgubeTSV = $this->vracljiveIzgubeTSV;
        $sistem->vracljiveIzgubeAux = $this->vracljiveIzgubeAux;

        return $sistem;
    }
}
