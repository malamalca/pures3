<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZracniHrup;

use App\Calc\Hrup\Elementi\Konstrukcija;
use App\Lib\Calc;
use App\Lib\EvalMath;

class LocilniElement
{
    public string $idKonstrukcije;
    public ?string $idDodatnegaSloja1 = null;
    public ?string $idDodatnegaSloja2 = null;
    public float $povrsina = 0;
    public float $povrsinskaMasa = 0;
    public float $Rw = 0;

    public array $pomozniElementi = [];

    private array $options = [];

    /**
     * @var \App\Calc\Hrup\Elementi\Konstrukcija $konstrukcija
     */
    public Konstrukcija $konstrukcija;

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
        $this->konstrukcija = $konstrukcija;
        $this->povrsinskaMasa = $konstrukcija->povrsinskaMasa;
        $this->options = $options;

        if ($config) {
            $this->parseConfig($config);
        }

        $this->analiza();
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

        $EvalMath = new EvalMath();

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            switch ($prop->getName()) {
                case 'konstrukcija':
                    break;
                case 'pomozniElementi':
                    if (isset($config->pomozniElementi)) {
                        foreach ((array)$config->pomozniElementi as $pomozniElement) {
                            $elObj = new \stdClass();
                            $elObj->naziv = $pomozniElement->naziv;
                            $elObj->Rw = (float)$pomozniElement->Rw;
                            $elObj->povrsina = (float)$pomozniElement->povrsina;
                            $elObj->stevilo = (int)$pomozniElement->stevilo;

                            $this->pomozniElementi[] = $elObj;
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
        $this->Rw = $this->konstrukcija->Rw + Calc::combineDeltaR(
            $this->konstrukcija,
            $this->idDodatnegaSloja1,
            $this->konstrukcija,
            $this->idDodatnegaSloja2
        );

        if (count($this->pomozniElementi) > 0) {
            $tau = 0;
            $povrsinaPomoznih = 0;
            foreach ($this->pomozniElementi as $pomozniElement) {
                $tau += $pomozniElement->povrsina / $this->povrsina *
                    pow(10, -$pomozniElement->Rw / 10) * $pomozniElement->stevilo;
                $povrsinaPomoznih += $pomozniElement->povrsina * $pomozniElement->stevilo;
            }

            $tau += ($this->povrsina - $povrsinaPomoznih) / $this->povrsina * pow(10, -$this->Rw / 10);

            $this->Rw = -10 * log10($tau);
        }
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $locilniElement = new \stdClass();

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if ($prop->isInitialized($this) && !is_null($prop->getValue($this))) {
                $locilniElement->{$prop->getName()} = $prop->getValue($this);
            }
        }

        return $locilniElement;
    }
}
