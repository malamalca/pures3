<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire\RazvodAbstractProperties;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire\VrstaRazvodnihCevi;
use App\Lib\Calc;

class RazvodHlajenja extends Razvod
{
    public string $sistem = 'hladnavoda';

    public ?\stdClass $crpalka;

    public array $stUrDelovanjaNaDan = [0, 0, 0, 0, 0, 0, 0, 0, 0,0, 0, 0];

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        parent::parseConfig($config);

        if (is_string($config)) {
            $config = json_decode($config);
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // OBTOČNA ČRPALKA
        if (!empty($config->crpalka)) {
            $this->crpalka = $config->crpalka;
        }
    }

    /**
     * Analiza podsistema
     *
     * @param array $toplotneIzgube Toplotne izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function analiza($toplotneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $this->toplotneIzgube($toplotneIzgube, $sistem, $cona, $okolje, $params);
        $this->potrebnaElektricnaEnergija($toplotneIzgube, $sistem, $cona, $okolje, $params);
    }

    /**
     * Izračun toplotnih izgub končnega prenosnika
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->toplotneIzgube['hlajenje'][$mesec] = $vneseneIzgube[$mesec] * 0.05;
            $this->vracljiveIzgube['hlajenje'][$mesec] = 0;
        }

        return $this->toplotneIzgube;
    }

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    public function potrebnaElektricnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $generator = array_first_callback($sistem->generatorji);

        if (!empty($this->crpalka) && !empty($generator->nazivnaMoc)) {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $steviloUr = $sistem->steviloUrDelovanja($mesec, $cona, $okolje);

                if ($vneseneIzgube[$mesec] > 0) {
                    $this->potrebnaElektricnaEnergija['hlajenje'][$mesec] = $steviloUr * $this->crpalka->moc * 0.001;
                } else {
                    $this->potrebnaElektricnaEnergija['hlajenje'][$mesec] = 0;
                }

                $this->vracljiveIzgubeAux['hlajenje'][$mesec] = 0;
            }
        } else {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $this->potrebnaElektricnaEnergija['hlajenje'][$mesec] = 0;
                $this->vracljiveIzgubeAux['hlajenje'][$mesec] = 0;
            }
        }

        return $this->potrebnaElektricnaEnergija;
    }

    /**
     * Vrne dolžino cevi za podano vrsto razvodnih cevi
     *
     * @param \App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire\VrstaRazvodnihCevi $vrsta Vrsta razvodne cevi
     * @param \stdClass $cona Podatki cone
     * @return float
     */
    public function dolzinaCevi(VrstaRazvodnihCevi $vrsta, $cona)
    {
        return 0;
    }

    /**
     * Vrne zahtevano fiksno vrednost konstante/količine
     *
     * @param \App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire\RazvodAbstractProperties $property Količina/konstanta
     * @param array $options Dodatni parametri
     * @return int|float
     */
    public function getProperty(RazvodAbstractProperties $property, $options = [])
    {
        return 0;
    }
}
