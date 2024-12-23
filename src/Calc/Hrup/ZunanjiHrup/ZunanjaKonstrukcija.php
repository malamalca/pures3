<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZunanjiHrup;

use App\Calc\Hrup\Elementi\Konstrukcija;
use App\Lib\Calc;
use App\Lib\EvalMath;

class ZunanjaKonstrukcija
{
    public ?string $id;
    public string $idKonstrukcije;
    public float $povrsina = 0;
    public int $stevilo = 1;
    public float $Rw = 0;
    public float $C = 0;
    public float $Ctr = 0;

    public array $R;

    private array $options = [];

    /**
     * @var \App\Calc\Hrup\Elementi\Konstrukcija $konstrukcija
     */
    private Konstrukcija $konstrukcija;

    /**
     * Class Constructor
     *
     * @param \App\Calc\Hrup\Elementi\Konstrukcija $konstrukcija Konstrukcija iz knjižnice
     * @param \stdClass|string $config Configuration
     * @param array $options Možnosti izračuna
     * @return void
     */
    public function __construct($konstrukcija, $config = null, $options = [])
    {
        $this->options = $options;
        $this->konstrukcija = $konstrukcija;

        $this->R = $konstrukcija->R;

        array_walk($this->R, function ($value, $key) {
            $this->R[$key] = $value + $this->konstrukcija->dR;
        });

        $this->Rw = Calc::Rw($this->R);
        $this->C =
            $this->konstrukcija->C ?? Calc::C(R: $this->R, povrsinskaMasa: $this->konstrukcija->povrsinskaMasa);

        $this->Ctr =
            $this->konstrukcija->Ctr ?? Calc::Ctr(R: $this->R, povrsinskaMasa: $this->konstrukcija->povrsinskaMasa);

        //$this->Rw = $this->konstrukcija->Rw + $this->konstrukcija->dR;
        //$this->C = $this->konstrukcija->C;
        //$this->Ctr = $this->konstrukcija->Ctr;

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
        $zunanjaKonstrukcija = new \stdClass();

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if ($prop->isInitialized($this)) {
                $zunanjaKonstrukcija->{$prop->getName()} = $prop->getValue($this);
            }
        }

        return $zunanjaKonstrukcija;
    }
}
