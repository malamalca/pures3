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
        $generator = array_first_callback($sistem->generatorji, fn($g) => true);

        if (!empty($this->crpalka) && !empty($generator->nazivnaMoc)) {
            // če električna moč obtočne črpalke ni podana, jo določimo iz hidravlične moči
            if (empty($this->crpalka->moc)) {
                $hidravlicnaMoc = $this->izracunHidravlicneMoci($sistem, $cona, $okolje);
                $fe_crpalke = $this->izracunFaktorjaRabeEnergijeCrpalke($hidravlicnaMoc);
                $this->crpalka->moc = $hidravlicnaMoc * $fe_crpalke;
            }

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
     * Izračun hidravlične moči obtočne črpalke hladilnega razvoda [W].
     * Analogno izračunu pri ogrevanju (SIST EN 15316-3, enačbe 66 in 67), prilagojeno hladilnemu mediju
     * (ohlajena voda 8/14 °C).
     *
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return float
     */
    public function izracunHidravlicneMoci($sistem, $cona, $okolje)
    {
        $Lmax = $this->getProperty(RazvodAbstractProperties::Lmax, ['cona' => $cona]);

        // ΔθC – temperaturna razlika hladilnega medija (ohlajena voda 8/14 °C) [°C]
        $deltaT = 6;

        // V – volumski pretok hladilnega medija [m³/h] (analogno enačbi 66)
        $volumskiPretok = $sistem->standardnaMoc($cona, $okolje) / (1.15 * $deltaT);

        // ΔpWE – tlačni padec generatorja hladu (uparjalnik) [kPa]
        $deltaP_WE = 20;

        // tlačni padec hladilnega razvoda [kPa] (analogno enačbi 67, brez dodatka za ploskovno ogrevanje)
        $deltaP = 0.13 * $Lmax + 2 + $deltaP_WE;

        // hidravlična moč črpalke v načrtovani obratovalni točki [W]
        $mocCrpalke = 0.2778 * $deltaP * $volumskiPretok;

        return $mocCrpalke;
    }

    /**
     * Izračun faktorja rabe energije obtočne črpalke (analogno enačbi 68, spodnji del).
     *
     * @param float $hidravlicnaMoc Hidravlična moč [W]
     * @return float
     */
    public function izracunFaktorjaRabeEnergijeCrpalke($hidravlicnaMoc)
    {
        $faktorCrpalkaPoProjektu = 1;

        $fe_crpalke = $hidravlicnaMoc > 0 ?
            1.25 + pow(200 / $hidravlicnaMoc, 0.5) * $faktorCrpalkaPoProjektu :
            0;

        return $fe_crpalke;
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
