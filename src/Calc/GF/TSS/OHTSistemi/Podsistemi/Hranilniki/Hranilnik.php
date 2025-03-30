<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Hranilniki;

use App\Calc\GF\TSS\TSSInterface;

abstract class Hranilnik extends TSSInterface
{
    public float $volumen;
    public int $stevilo = 1;

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

        return $sistem;
    }
}
