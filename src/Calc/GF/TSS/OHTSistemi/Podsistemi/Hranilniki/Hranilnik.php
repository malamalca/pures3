<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Hranilniki;

use App\Calc\GF\TSS\TSSInterface;

abstract class Hranilnik extends TSSInterface
{
    public float $volumen;
    public int $stevilo = 1;

    /**
     * Dnevne toplotne izgube hranilnika v stanju obratovalne pripravljenosti pri ΔT = 45 K [kWh/24h]
     * (podatek proizvajalca); kadar ni podan, se uporabi enačba iz standarda.
     */
    public ?float $dnevneIzgube = null;

    /**
     * Class Constructor
     *
     * @param \stdClass|string|null $config Configuration
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

        $this->volumen = $config->volumen ?? 0;
        $this->id = $config->id ?? null;

        // podatek proizvajalca: dnevne izgube [kWh/24h] ali stalne izgube [W], oboje pri ΔT = 45 K
        if (isset($config->dnevneIzgube)) {
            $this->dnevneIzgube = (float)$config->dnevneIzgube;
        } elseif (isset($config->stalneIzgube)) {
            $this->dnevneIzgube = (float)$config->stalneIzgube * 24 / 1000;
        }
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $sistem = parent::export();
        $sistem->volumen = $this->volumen;
        if (isset($this->dnevneIzgube)) {
            $sistem->dnevneIzgube = $this->dnevneIzgube;
        }

        return $sistem;
    }
}
