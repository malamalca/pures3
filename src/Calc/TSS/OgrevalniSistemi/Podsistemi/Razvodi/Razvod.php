<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi\Podsistemi\Razvodi;

use App\Calc\TSS\OgrevalniSistemi\Podsistemi\Razvodi\Izbire\RazvodAbstractProperties;
use App\Calc\TSS\OgrevalniSistemi\Podsistemi\Razvodi\Izbire\VrstaRazvodnihCevi;

abstract class Razvod
{
    public string $sistem = 'razvod';

    public ElementRazvoda $horizontalniVod;
    public ElementRazvoda $dvizniVod;
    public ElementRazvoda $prikljucniVod;

    public ?string $id;
    public ?string $idPrenosnika;

    public array $toplotneIzgube = [];
    public array $vracljiveIzgube = [];
    public array $vracljiveIzgubeAux = [];
    public array $potrebnaElektricnaEnergija = [];

    /**
     * Class Constructor
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function __construct($config = null)
    {
        if ($config) {
            $this->parseConfig($config);
        }
    }

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->id = $config->id ?? null;
        $this->idPrenosnika = $config->idPrenosnika ?? null;
    }

    /**
     * Vrne zahtevano fiksno vrednost konstante/količine
     *
     * @param \App\Calc\TSS\OgrevalniSistemi\Podsistemi\Razvodi\Izbire\RazvodAbstractProperties $property Količina/konstanta
     * @param array $options Dodatni parametri
     * @return int|float
     */
    abstract public function getProperty(RazvodAbstractProperties $property, $options = []);

    /**
     * Vrne dolžino cevi za podano vrsto razvodnih cevi
     *
     * @param \App\Calc\TSS\OgrevalniSistemi\Podsistemi\Razvodi\Izbire\VrstaRazvodnihCevi $vrsta Vrsta razvodne cevi
     * @param \stdClass $cona Podatki cone
     * @return float
     */
    abstract public function dolzinaCevi(VrstaRazvodnihCevi $vrsta, $cona);

    /**
     * Izračun toplotnih izgub končnega prenosnika
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    abstract public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = []);

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\TSS\OgrevalniSistemi\OgrevalniSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return array
     */
    abstract public function potrebnaElektricnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = []);

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $sistem = new \stdClass();
        $sistem->id = $this->id;
        $sistem->sistem = $this->sistem;

        $sistem->toplotneIzgube = $this->toplotneIzgube;
        $sistem->potrebnaElektricnaEnergija = $this->potrebnaElektricnaEnergija;
        $sistem->vracljiveIzgube = $this->vracljiveIzgube;
        $sistem->vracljiveIzgubeAux = $this->vracljiveIzgubeAux;

        $sistem->vodi = [];
        $sistem->vodi[] = $this->horizontalniVod->export();
        $sistem->vodi[] = $this->dvizniVod->export();
        $sistem->vodi[] = $this->prikljucniVod->export();

        return $sistem;
    }
}
