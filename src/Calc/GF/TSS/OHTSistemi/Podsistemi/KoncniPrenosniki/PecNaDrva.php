<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\KoncniPrenosniki;

use App\Lib\Calc;

class PecNaDrva extends KoncniPrenosnik
{
    public string $vrsta = 'Peč ali kamin na drva, krušna peč';

    public float $deltaT_hydr = 0.0;
    public float $deltaT_emb = 0.0;
    public float $deltaT_im = 0.0;
    public float $deltaT_sol = 0.0;

    public int $stEtaz = 1;

    /**
     * Izračun toplotnih izgub končnega prenosnika
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $this->deltaT_str = $cona->steviloEtaz > 1 ? 1.4 : 0.9;

        return parent::toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params);
    }

    /**
     * Uporabljena obnovljiva energija iz okolja
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function vracljiveIzgubeAux($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->vracljiveIzgubeAux[$mesec] = 0;
        }

        return $this->vracljiveIzgubeAux;
    }
}
