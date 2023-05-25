<?php
declare(strict_types=1);

namespace App\Calc\TSS\Energenti;

class Elektrika extends Energent
{
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
    }
}
