<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZunanjiHrup;

use App\Calc\Hrup\Elementi\MaliElement;
use App\Lib\EvalMath;

class ZunanjiMaliElement
{
    public string $idMaliElement;
    public float $povrsina = 0;
    public ?float $dolzina;
    public int $stevilo = 1;
    public array $R = [];
    public float $Rw = 0;
    public float $C = 0;
    public float $Ctr = 0;

    private array $options = [];

    /**
     * @var \App\Calc\Hrup\Elementi\MaliElement $maliElement
     */
    private MaliElement $maliElement;

    /**
     * Class Constructor
     *
     * @param \App\Calc\Hrup\Elementi\MaliElement $maliElement Konstrukcija iz knjižnice
     * @param \stdClass|string $config Configuration
     * @param array $options Možnosti izračuna
     * @return void
     */
    public function __construct($maliElement, $config = null, $options = [])
    {
        $this->options = $options;
        $this->maliElement = $maliElement;

        $this->R = $this->maliElement->R;
        $this->Rw = $this->maliElement->Rw;
        $this->C = $this->maliElement->C;
        $this->Ctr = $this->maliElement->Ctr;

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
        if (!empty($this->dolzina)) {
            $this->Rw += round(-10 * log10($this->dolzina), 0);
            array_walk($this->R, function ($R, $fq) {
                $this->R[$fq] += round(-10 * log10($this->dolzina), 0);
            });
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
