<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZracniHrup;

use App\Calc\Hrup\Elementi\Konstrukcija;
use App\Lib\EvalMath;

class ZracniHrup
{
    public string $id = '';
    public string $naziv = '';

    public float $povrsina = 0;

    public float $Rw = 0;
    public float $minRw = 0;

    public LocilniElement $locilniElement;
    public array $stranskiElementi;

    private array $options = [];

    private array $konstrukcijeLib;

    /**
     * Class Constructor
     *
     * @param array $konstrukcijeLib Knjižnica konstrukcij
     * @param \stdClass $config Configuration
     * @param array $options Možnosti izračuna
     * @return void
     */
    public function __construct($konstrukcijeLib, $config = null, $options = [])
    {
        $this->konstrukcijeLib = $konstrukcijeLib;
        $this->options = $options;

        $konstrukcijaConfig = array_first(
            $this->konstrukcijeLib,
            fn($k) => $k->id == $config->locilniElement->idKonstrukcije
        );
        $this->locilniElement = new LocilniElement(
            new Konstrukcija($konstrukcijaConfig),
            $config->locilniElement
        );

        if ($config) {
            $this->parseConfig($config);
        }
    }

    /**
     * Loads configuration from json|stdClass
     *
     * @param \stdClass $config Configuration
     * @return void
     */
    protected function parseConfig($config)
    {
        $EvalMath = EvalMath::getInstance(['decimalSeparator' => '.', 'thousandsSeparator' => '']);

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            switch ($prop->getName()) {
                case 'locilniElement':
                    break;
                case 'stranskiElementi':
                    foreach ($config->stranskiElementi as $stranskiElementiConfig) {
                        $libKonstrukcijaConfig = array_first(
                            $this->konstrukcijeLib,
                            fn($kons) => $stranskiElementiConfig->idKonstrukcije == $kons->id
                        );
                        if (!$libKonstrukcijaConfig) {
                            throw new \Exception(sprintf(
                                'Konstrukcija stranskega elementa "%s" v knjižnici ne obstaja.',
                                $stranskiElementiConfig->idKonstrukcije
                            ));
                        }
                        $stranskiElement = new StranskiElement(
                            new Konstrukcija($libKonstrukcijaConfig),
                            new Konstrukcija($libKonstrukcijaConfig),
                            $this->locilniElement,
                            $stranskiElementiConfig
                        );
                        $this->stranskiElementi[] = $stranskiElement;
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
     * @param \stdClass|null $splosniPodatki Splošni podatki
     * @return void
     */
    public function analiza($splosniPodatki = null)
    {
        $tau = pow(10, -($this->locilniElement->konstrukcija->Rw + $this->locilniElement->konstrukcija->dR) / 10);

        foreach ($this->stranskiElementi as $stranskiElement) {
            $tau += pow(10, -$stranskiElement->R_Df / 10);
            $tau += pow(10, -$stranskiElement->R_Ff / 10);
            $tau += pow(10, -$stranskiElement->R_Fd / 10);
        }

        $this->Rw = -10 * log10($tau);
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $locilnaKonstrukcija = new \stdClass();

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if ($prop->isInitialized($this)) {
                if ($prop->getName() == 'stranskiElementi') {
                    $locilnaKonstrukcija->stranskiElementi = [];
                    foreach ($this->stranskiElementi as $stranskiElement) {
                        $locilnaKonstrukcija->stranskiElementi[] = $stranskiElement->export();
                    }
                } else {
                    $locilnaKonstrukcija->{$prop->getName()} = $prop->getValue($this);
                }
            }
        }

        return $locilnaKonstrukcija;
    }
}
