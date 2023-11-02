<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZunanjiHrup;

use App\Calc\Hrup\Elementi\OknaVrata;
use App\Lib\EvalMath;

class ZunanjaOknaVrata
{
    public string $idOknaVrata;
    public float $povrsina = 0;
    public int $stevilo = 1;
    public float $Rw = 0;
    public float $C = 0;
    public float $Ctr = 0;

    private array $options = [];

    /**
     * @var \App\Calc\Hrup\Elementi\OknaVrata $oknaVrata
     */
    private OknaVrata $oknaVrata;

    /**
     * Class Constructor
     *
     * @param \App\Calc\Hrup\Elementi\OknaVrata $oknaVrata Konstrukcija iz knjižnice
     * @param \stdClass|string $config Configuration
     * @param array $options Možnosti izračuna
     * @return void
     */
    public function __construct($oknaVrata, $config = null, $options = [])
    {
        $this->options = $options;
        $this->oknaVrata = $oknaVrata;

        $this->Rw = $this->oknaVrata->Rw;
        $this->C = $this->oknaVrata->C;
        $this->Ctr = $this->oknaVrata->Ctr;

        if ($this->oknaVrata->vrsta == 'okno') {
            $this->Rw -= 2;
        }
        if ($this->oknaVrata->vrsta == 'vrata') {
            $this->Rw -= 5;
        }

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

        $EvalMath = EvalMath::getInstance(['decimalSeparator' => '.', 'thousandsSeparator' => '']);

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if (isset($config->{$prop->getName()})) {
                $configValue = $config->{$prop->getName()};
                if (
                    $prop->isInitialized($this) &&
                    in_array(gettype($this->{$prop->getName()}), ['double', 'int']) &&
                    gettype($configValue) == 'string'
                ) {
                    $configValue = (float)$EvalMath->e($configValue);
                }
                $this->{$prop->getName()} = $configValue;
            }
        }
    }

    /**
     * Glavna funkcija za analizo cone
     *
     * @return void
     */
    public function analiza()
    {
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $oknaVrata = new \stdClass();

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if ($prop->isInitialized($this)) {
                $oknaVrata->{$prop->getName()} = $prop->getValue($this);
            }
        }

        return $oknaVrata;
    }
}
