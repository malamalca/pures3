<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi;

class SplitHladilniOHTSistem extends OHTSistem
{
    public bool $jeOgrevalniSistem = false;

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

        // postavim array hlajenja
        $config->hlajenje = new \stdClass();
        $config->hlajenje->generatorji = ['splitHlajenje'];

        $config->id = 'splitHlajenje';
        $config->generatorji = [$config];

        parent::parseConfig($config);
    }

    /**
     * @inheritdoc
     */
    public function standardnaMoc($cona, $okolje): float
    {
        return $this->generatorji[0]->nazivnaMoc;
    }
}
