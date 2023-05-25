<?php
declare(strict_types=1);

namespace App\Calc\TSS\Generatorji;

abstract class Generator
{
    public string $id;

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
    public function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->id = $config->id;
    }
}
