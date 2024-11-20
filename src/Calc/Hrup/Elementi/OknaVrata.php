<?php
declare(strict_types=1);

namespace App\Calc\Hrup\Elementi;

use App\Lib\EvalMath;

class OknaVrata
{
    public string $id;
    public string $naziv;
    public string $tip = 'vertikalna';
    public bool $tsgDeltaR = true;
    public string $vrsta;
    public array $R = [];
    public float $Rw = 0;
    public float $C = 0;
    public float $Ctr = 0;
    public ?float $dR;

    public array $options = [];

    /**
     * Class Constructor
     *
     * @param \stdClass|null $config Konstrukcija iz knjižnice
     * @param array $options Možnosti izračuna
     * @return void
     */
    public function __construct($config = null, $options = [])
    {
        $this->options = $options;

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
            switch ($prop->getName()) {
                case 'R':
                    if (isset($config->R)) {
                        $this->R = (array)json_decode((string)json_encode($config->R), true);
                    }
                    break;
                default:
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

        if (!isset($this->dR)) {
            if ($this->vrsta == 'okno') {
                $this->dR = -2;
            }
            if ($this->vrsta == 'vrata') {
                $this->dR = -5;
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
        if (empty($this->R)) {
            $this->R = [500 => $this->Rw];
        }
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
