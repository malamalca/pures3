<?php
declare(strict_types=1);

namespace App\Calc\GF\Stavbe;

use App\Calc\GF\Stavbe\Izbire\VrstaGradnje;
use App\Calc\GF\Stavbe\Izbire\VrstaZahtevnosti;
use stdClass;

abstract class Stavba
{
    public string $naziv;
    public string $lokacija;
    public string $KO;
    public array $parcele;
    public stdClass $koordinate;
    public string $klasifikacija;

    public VrstaGradnje $tip;
    public VrstaZahtevnosti $zahtevnost;
    public bool $javna;

    public array $cone = [];
    public array $sistemi = [];

    /**
     * Class Constructor
     *
     * @param \stdClass|null $config Configuration
     * @return void
     */
    public function __construct(?stdClass $config)
    {
        if ($config) {
            $this->parseConfig($config);
        }
    }

    /**
     * Loads configuration from json|stdClass
     *
     * @param \stdClass|null $config Configuration
     * @return void
     */
    protected function parseConfig(?stdClass $config)
    {
        $this->naziv = $config->naziv;
        $this->lokacija = $config->lokacija;
        $this->KO = $config->KO;
        $this->parcele = $config->parcele;
        $this->koordinate = $config->koordinate;
        $this->klasifikacija = $config->klasifikacija;
        $this->tip = VrstaGradnje::from($config->tip);
        $this->zahtevnost = VrstaZahtevnosti::from($config->vrsta);
        $this->javna = $config->javna;
    }

    /**
     * Analiza stavbe
     *
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    abstract public function analiza($okolje);

    /**
     * Analiza sistemov
     *
     * @return void
     */
    abstract public function analizaTSS();

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $stavba = new \stdClass();
        $stavba->naziv = $this->naziv;
        $stavba->lokacija = $this->lokacija;
        $stavba->KO = $this->lokacija;
        $stavba->parcele = $this->lokacija;
        $stavba->koordinate = $this->lokacija;
        $stavba->klasifikacija = $this->klasifikacija;
        $stavba->tip = $this->tip->value;
        $stavba->vrsta = $this->zahtevnost->value;
        $stavba->javna = $this->javna;

        return $stavba;
    }
}
