<?php
declare(strict_types=1);

namespace App\Calc\Hrup\Elementi;

use App\Lib\Calc;
use App\Lib\EvalMath;

class MaliElement
{
    public string $id;
    public string $naziv;
    public string $tip = 'vertikalna';
    public array $R = [];
    public float $Rw = 0;
    public float $C = 0;
    public float $Ctr = 0;

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
                        //$this->R = (array)json_decode((string)json_encode($config->R), true);
                        $Rarr = (array)json_decode((string)json_encode($config->R), true);
                        if (sizeof($Rarr) == 1) {
                            $this->R[500] = isset($Rarr[500]) ? $Rarr[500] : $Rarr[0];
                        }
                        if (sizeof($Rarr) == sizeof(Calc::FREKVENCE_TERCE)) {
                            foreach (Calc::FREKVENCE_TERCE as $i => $fq) {
                                $this->R[$fq] = isset($Rarr[$fq]) ? $Rarr[$fq] : $Rarr[$i];
                            }
                        }
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
        $maliElement = new \stdClass();

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if ($prop->isInitialized($this)) {
                $maliElement->{$prop->getName()} = $prop->getValue($this);
            }
        }

        return $maliElement;
    }
}
