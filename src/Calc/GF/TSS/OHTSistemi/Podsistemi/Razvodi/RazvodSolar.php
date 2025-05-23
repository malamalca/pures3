<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire\RazvodAbstractProperties;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Razvodi\Izbire\VrstaRazvodnihCevi;
use App\Calc\GF\TSS\TSSPorociloNiz;
use App\Calc\GF\TSS\TSSPorociloPodatek;
use App\Lib\Calc;

class RazvodSolar extends Razvod
{
    public string $sistem = 'solar';

    public string $idGeneratorja;
    public ?\stdClass $crpalka;

    public array $stUrDelovanjaObtocneCrpalke;
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
        } else {
            $this->crpalka = new \stdClass();
            $this->crpalka->moc = 0;
        }

        $this->idGeneratorja = $config->idGeneratorja;
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
        if (!isset($this->crpalka->moc)) {
            $generator = array_first($sistem->generatorji, fn($generatorj) => $generatorj->id == $this->idGeneratorja);
            if (!$generator) {
                throw new \Exception('TSS RazvodSolar: Generator ne obstaja.');
            }

            // EN 15316-4-3:2017
            // B.2.1.2 Collector pump
            $c1 = 25; // [W]
            $c2 = 2; // [W/m2]

            $this->crpalka->moc = $c1 + $c2 * $generator->povrsina;
        }

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
        $namen = $params['namen'];

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->potrebnaElektricnaEnergija[$namen][$mesec] = 0;

            // B.2.2.5 Collector loop overall heat loss coefficient
            // not implemented
            // If the collector loop characteristics are not known, the heat loss coefficient of the piping in de the
            // collector loop is calculated by
            // H_loop;p = c1 + c2 * A_sol [W/K]
            // c1 = 5.0 W/K
            // c2 = 0.5 W/K

            $this->toplotneIzgube[$namen][$mesec] = 0;
            $this->vracljiveIzgube[$namen][$mesec] = 0;
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
        $namen = $params['namen'];

        $generator = array_first($sistem->generatorji, fn($generatorj) => $generatorj->id == $this->idGeneratorja);
        if (!$generator) {
            throw new \Exception('TSS RazvodSolar: Generator ne obstaja.');
        }
        $generator->izracunSoncnegaObsevanja($okolje);

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stDniDelovanja = $stDni;

            ////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // obtočna črpalka za vodo, ki kroži v kolektorjih
            // EN 15316-4-3:2017
            // heat loss coefficient of the piping in de the collector loop is calculated by

            // Table B.13 — Default values for the collector pump operation time t_aux;an
            // 2000h for systems designed for the water heating service or for both water and space heating service (SOL_USE = WHS or SOL_USE = COMBI)
            // 1500h for systems designed for the space heating service only (SOL_USE = SHS)

            $this->stUrDelovanjaObtocneCrpalke[$mesec] =
                2000 * $generator->soncnoObsevanje[$mesec] * $stDniDelovanja / $generator->skupnoSoncnoObsevanje;

            // električna energija za obtočno črpalko, ki poganja vodo po SSE
            $W_p_sol = $this->crpalka->moc * $this->stUrDelovanjaObtocneCrpalke[$mesec] / 1000;

            $this->potrebnaElektricnaEnergija[$namen][$mesec] = $W_p_sol;

            $this->vracljiveIzgubeTSV[$namen][$mesec] = 0.25 * $W_p_sol;
            $this->vracljiveIzgubeAux[$namen][$mesec] = 0.25 * $W_p_sol;
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

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $this->porociloNizi[] = new TSSPorociloNiz(
            'tp',
            't<sub>p</sub>',
            'ur',
            $this->stUrDelovanjaNaDan,
            1
        );
        $this->porociloPodatki[] = new TSSPorociloPodatek(
            'P<sub>pump,dis,sc</sub>',
            'Moč solarne obtočne črpalke',
            $this->crpalka->moc,
            'W'
        );

        $sistem = parent::export();

        return $sistem;
    }
}
