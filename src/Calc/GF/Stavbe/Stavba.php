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
    public int $year;

    /**
     * Predpisanega ROVE ni mogoče zagotoviti niti s tehnologijami OVE v bližini stavbe
     * niti z deležem oddaljenih sistemov (drugi in tretji odstavek 14. člena pravilnika).
     * Šele takrat je dopustna uporaba kompenzacijskega faktorja YROVE (četrti odstavek).
     * Privzeto drži - predpostavi se, da stavba nima oddaljenih sistemov oziroma
     * solastniških deležev, s katerimi bi ROVE lahko dokazala.
     *
     * @var bool $roveNiMogoceZagotoviti
     */
    public bool $roveNiMogoceZagotoviti = true;

    public array $cone = [];
    public array $sistemi = [];

    /**
     * Class Constructor
     *
     * @param \stdClass|null $config Configuration
     * @param int $year Leto, za katerega se izvaja izračun
     * @return void
     */
    public function __construct(?stdClass $config, int $year)
    {
        $this->year = $year;
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
        $this->roveNiMogoceZagotoviti = $config->roveNiMogoceZagotoviti ?? true;
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
        $stavba->KO = $this->KO;
        $stavba->parcele = $this->parcele;
        $stavba->koordinate = $this->koordinate;
        $stavba->klasifikacija = $this->klasifikacija;
        $stavba->tip = $this->tip->value;
        $stavba->vrsta = $this->zahtevnost->value;
        $stavba->javna = $this->javna;
        $stavba->roveNiMogoceZagotoviti = $this->roveNiMogoceZagotoviti;

        return $stavba;
    }
}
