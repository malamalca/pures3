<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi;

use App\Calc\TSS\Energenti\Energent;
use App\Calc\TSS\GeneratorFactory;
use App\Calc\TSS\HranilnikFactory;
use App\Calc\TSS\KoncniPrenosnikFactory;
use App\Calc\TSS\RazvodFactory;

abstract class OgrevalniSistem
{
    public Energent $energent;

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
}
