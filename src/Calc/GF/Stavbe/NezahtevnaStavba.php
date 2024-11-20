<?php
declare(strict_types=1);

namespace App\Calc\GF\Stavbe;

use stdClass;

class NezahtevnaStavba extends Stavba
{
    public float $ogrevanaPovrsina;
    public stdClass $vgrajeniSistemi;

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    protected function parseConfig($config)
    {
        parent::parseConfig($config);
        $this->ogrevanaPovrsina = $config->ogrevanaPovrsina;
        $this->vgrajeniSistemi = $config->vgrajeniSistemi;
    }

    /**
     * Analiza stavbe
     *
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public function analiza($okolje)
    {
    }

    /**
     * Glavna metoda za analizo TSS
     *
     * @return void
     */
    public function analizaTSS()
    {
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $stavba = parent::export();

        $reflect = new \ReflectionClass(NezahtevnaStavba::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            $stavba->{$prop->getName()} = $prop->getValue($this);
        }

        return $stavba;
    }
}
