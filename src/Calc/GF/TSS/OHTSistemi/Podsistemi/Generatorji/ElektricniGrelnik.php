<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji;

use App\Lib\Calc;

class ElektricniGrelnik extends Generator
{
    public bool $znotrajOvoja = true;

    /**
     * Class Constructor
     *
     * @param \stdClass $config Configuration
     * @return void
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
    }

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        parent::parseConfig($config);
    }

    /**
     * Izračun potrebne energije generatorja
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        switch ($namen) {
            case 'ogrevanje':
                $this->toplotneIzgubeOgrevanje($vneseneIzgube, $sistem, $cona, $okolje, $params);
                break;
            case 'tsv':
                $this->toplotneIzgubeTSV($vneseneIzgube, $sistem, $cona, $okolje, $params);
                break;
        }
    }

    /**
     * Izračun potrebne energije generatorja v sistemu ST
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    private function toplotneIzgubeTSV($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->vneseneIzgube['tsv'][$mesec] = 0;
            $this->toplotneIzgube['tsv'][$mesec] = 0;

            $this->vracljiveIzgube['tsv'][$mesec] = 0;
        }
    }

    /**
     * Izračun potrebne energije generatorja v sistemu ogrevanja
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    private function toplotneIzgubeOgrevanje($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->vneseneIzgube['ogrevanje'][$mesec] = 0;
            $this->toplotneIzgube['ogrevanje'][$mesec] = 0;

            $this->vracljiveIzgube['ogrevanje'][$mesec] = 0;
        }
    }

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function potrebnaElektricnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        if ($namen == 'tsv') {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $this->potrebnaElektricnaEnergija['tsv'][$mesec] =
                    ($this->potrebnaElektricnaEnergija['tsv'][$mesec] ?? 0) + 0;

                $this->vracljiveIzgubeAux[$mesec] = 0;
            }
        }
        if ($namen == 'ogrevanje') {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $this->potrebnaElektricnaEnergija['ogrevanje'][$mesec] =
                    ($this->potrebnaElektricnaEnergija['ogrevanje'][$mesec] ?? 0) + 0;

                $this->vracljiveIzgubeAux[$mesec] = 0;
            }
        }
    }

    /**
     * Uporabljena obnovljiva energija iz okolja
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function obnovljivaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->obnovljivaEnergija[$namen][$mesec] = 0;
        }
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $sistem = parent::export();
        $sistem->znotrajOvoja = $this->znotrajOvoja;
        $sistem->nazivnaMoc = $this->nazivnaMoc;

        return $sistem;
    }
}
