<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi;

use App\Calc\TSS\Energenti\Energent;
use App\Calc\TSS\GeneratorFactory;
use App\Calc\TSS\KoncniPrenosnikFactory;
use App\Calc\TSS\OgrevalniSistemi\Izbire\VrstaRezima;
use App\Calc\TSS\RazvodFactory;

abstract class OgrevalniSistem
{
    public Energent $energent;
    public VrstaRezima $rezim;

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

    public array $koncniPrenosniki = [];
    public array $razvodi = [];
    public array $generatorji = [];

    public $potrebnaEnergija;
    public $potrebnaElektricnaEnergija;
    public $obnovljivaEnergija;
    public $vracljiveIzgube;

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
    protected function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->rezim = VrstaRezima::from($config->rezim);

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
     * @return array
     */
    abstract public function analiza($cona, $okolje);
}
