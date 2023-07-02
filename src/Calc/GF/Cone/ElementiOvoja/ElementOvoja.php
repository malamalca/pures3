<?php
declare(strict_types=1);

namespace App\Calc\GF\Stavbe\ElementiOvoja;

use App\Lib\Calc;

abstract class ElementOvoja
{
    public \stdClass $konstrukcija;

    public string $opis = '';
    public int $stevilo = 1;

    public string $orientacija = '';
    public int $naklon = 0;
    public float $povrsina = 0;

    // temperaturni korekcijski faktor
    public float $b = 1;
    public float $U = 0;

    // toplotni tok
    public float $H_ogrevanje = 0;
    public float $H_hlajenje = 0;

    public array $faktorSencenja = [];
    public array $soncnoObsevanje = [];

    public array $transIzgubeOgrevanje = [];
    public array $transIzgubeHlajenje = [];

    public array $solarniDobitkiOgrevanje = [];
    public array $solarniDobitkiHlajenje = [];

    /**
     * Class Constructor
     *
     * @param \stdClass $konstrukcija Podatki konstrukcije
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function __construct($konstrukcija, $config = null)
    {
        $this->konstrukcija = $konstrukcija;
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
    protected function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->opis = $config->opis ?? null;

        $this->stevilo = $config->stevilo ?? 1;

        $this->orientacija = $config->orientacija ?? '';
        $this->naklon = $config->naklon ?? 0;
        $this->povrsina = $config->povrsina ?? 0;

        $this->U = $this->konstrukcija->U;
        $this->faktorSencenja = $config->faktorSencenja ?? array_map(fn($m) => 1, Calc::MESECI);
    }

    /**
     * Analiza elementa
     *
     * @param \App\Calc\GF\Cone\Cona $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    abstract public function analiza($cona, $okolje);

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $elementOvoja = new \stdClass();
        $elementOvoja->idKonstrukcije = $this->konstrukcija->id;
        $elementOvoja->opis = $this->opis;

        $elementOvoja->stevilo = $this->stevilo;

        $elementOvoja->orientacija = $this->orientacija;
        $elementOvoja->naklon = $this->naklon;
        $elementOvoja->povrsina = $this->povrsina;

        $elementOvoja->b = $this->b;
        $elementOvoja->U = $this->U;

        $elementOvoja->H_ogrevanje = $this->H_ogrevanje;
        $elementOvoja->H_hlajenje = $this->H_hlajenje;

        $elementOvoja->faktorSencenja = $this->faktorSencenja;
        $elementOvoja->soncnoObsevanje = $this->soncnoObsevanje;

        $elementOvoja->transIzgubeOgrevanje = $this->transIzgubeOgrevanje;
        $elementOvoja->transIzgubeHlajenje = $this->transIzgubeHlajenje;

        $elementOvoja->solarniDobitkiOgrevanje = $this->solarniDobitkiOgrevanje;
        $elementOvoja->solarniDobitkiHlajenje = $this->solarniDobitkiHlajenje;

        return $elementOvoja;
    }
}
