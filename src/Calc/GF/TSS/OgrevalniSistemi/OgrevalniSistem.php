<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi;

use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\GeneratorFactory;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\HranilnikFactory;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosnikFactory;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\RazvodFactory;
use App\Calc\GF\TSS\TSSVrstaEnergenta;

abstract class OgrevalniSistem
{
    public ?string $id;
    public ?string $idCone;
    public string $vrsta;
    public bool $jeOgrevalniSistem = true;
    public TSSVrstaEnergenta $energent;

    /**
     * QN – standardna potrebna toplotna moč za ogrevanje (cone) – moč ogreval, skladno s SIST
     * EN 12831 ali z drugimi enakovrednimi, v stroki priznanimi računskimi metodami [kW]
     *
     * @var float $standardnaMoc
     */
    public float $standardnaMoc;

    /**
     * Povprecna obremenitev podsistemov
     */
    public array $povprecnaObremenitev;

    public array $podsistemi = [];

    public array $koncniPrenosniki = [];
    public array $razvodi = [];
    public array $hranilniki = [];
    public array $generatorji = [];

    public array $potrebnaEnergija = [];
    public array $potrebnaElektricnaEnergija = [];
    public array $obnovljivaEnergija = [];
    public array $vracljiveIzgube = [];

    public array $energijaPoEnergentih = [];

    // array namenjen da se vnaša vračljiva energija iz drugih sistemov
    public array $vracljiveIzgubeVOgrevanje = [];

    public float $letnaUcinkovitostOgrHlaTsv = 0;
    public float $minLetnaUcinkovitostOgrHlaTsv = 0;

    /**
     * Class Constructor
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function __construct($config = null)
    {
        $energijaPoEnergentih = [];
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

        $this->id = $config->id ?? null;
        $this->idCone = $config->idCone ?? null;
        $this->vrsta = $config->vrsta;
        $this->energent = TSSVrstaEnergenta::from($config->energent ?? 'elektrika');

        if (!empty($config->razvodi)) {
            foreach ($config->razvodi as $razvod) {
                $this->razvodi[] = RazvodFactory::create($razvod->vrsta, $razvod);
            }
        }

        if (!empty($config->prenosniki)) {
            foreach ($config->prenosniki as $prenosnik) {
                $this->koncniPrenosniki[] = KoncniPrenosnikFactory::create($prenosnik->vrsta, $prenosnik);
            }
        }

        if (!empty($config->hranilniki)) {
            foreach ($config->hranilniki as $hranilnik) {
                $this->hranilniki[] = HranilnikFactory::create($hranilnik->vrsta, $hranilnik);
            }
        }

        if (!empty($config->generatorji)) {
            foreach ($config->generatorji as $generator) {
                $this->generatorji[] = GeneratorFactory::create($generator->vrsta, $generator);
            }
        }
    }

    /**
     * Analiza ogrevalnega sistem
     *
     * @param \stdClass $cona Podatki cone
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
        $sistem = new \stdClass();
        $sistem->id = $this->id;
        $sistem->idCone = $this->idCone;
        $sistem->vrsta = $this->vrsta;
        $sistem->energent = $this->energent;
        $sistem->jeOgrevalniSistem = $this->jeOgrevalniSistem;

        $sistem->potrebnaEnergija = $this->potrebnaEnergija;
        $sistem->potrebnaElektricnaEnergija = $this->potrebnaElektricnaEnergija;
        $sistem->obnovljivaEnergija = $this->obnovljivaEnergija;
        $sistem->vracljiveIzgube = $this->vracljiveIzgube;

        $sistem->energijaPoEnergentih = $this->energijaPoEnergentih;

        $sistem->letnaUcinkovitostOgrHlaTsv = $this->letnaUcinkovitostOgrHlaTsv;
        $sistem->minLetnaUcinkovitostOgrHlaTsv = $this->minLetnaUcinkovitostOgrHlaTsv;

        if (!empty($this->koncniPrenosniki)) {
            $sistem->prenosniki = [];
            foreach ($this->koncniPrenosniki as $prenosnik) {
                $sistem->prenosniki[] = $prenosnik->export();
            }
        }

        if (!empty($this->razvodi)) {
            $sistem->razvodi = [];
            foreach ($this->razvodi as $razvod) {
                $sistem->razvodi[] = $razvod->export();
            }
        }

        if (!empty($this->hranilniki)) {
            $sistem->hranilniki = [];
            foreach ($this->hranilniki as $hranilnik) {
                $sistem->hranilniki[] = $hranilnik->export();
            }
        }

        if (!empty($this->generatorji)) {
            $sistem->generatorji = [];
            foreach ($this->generatorji as $generator) {
                $sistem->generatorji[] = $generator->export();
            }
        }

        return $sistem;
    }
}
