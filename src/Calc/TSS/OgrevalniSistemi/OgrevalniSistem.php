<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi;

use App\Calc\TSS\Energenti\Energent;
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

    public array $izgubePrenosnikov;
    protected array $razvodi = [];
    protected array $koncniPrenosniki = [];

    /**
     * Class Constructor
     *
     * @param string|\StdClass $config Configuration
     * @return void
     */
    public function __construct($config = null)
    {
        if ($config) {
            $this->parseConfig($config);
        }
    }

    /**
     * Loads configuration from json|StdClass
     *
     * @param string|\StdClass $config Configuration
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
    }

    /**
     * Analiza ogrevalnega sistem
     *
     * @param \StdClass $cona Podatki cone
     * @param \StdClass $okolje Podatki okolja
     * @return array
     */
    abstract public function analiza($cona, $okolje);
}
