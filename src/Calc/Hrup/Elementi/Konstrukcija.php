<?php
declare(strict_types=1);

namespace App\Calc\Hrup\Elementi;

use App\Calc\Hrup\Elementi\Izbire\VrstaDodatnegaSloja;
use App\Lib\EvalMath;

class Konstrukcija
{
    public string $id;
    public string $naziv;
    public string $tip = 'vertikalna';
    public float $povrsinskaMasa = 0;
    public float $Rw = 0;
    public float $C = 0;
    public float $Ctr = 0;

    public array $dRw = [];
    public array $dodatniSloji = [];

    private array $options = [];

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
                case 'dodatniSloji':
                    $dodatniSlojiNazivi = ['znotaj', 'zgoraj', 'zunaj', 'spodaj'];
                    foreach ($dodatniSlojiNazivi as $dodatniSlojNaziv) {
                        if (isset($config->dodatniSloji->$dodatniSlojNaziv)) {
                            $config->dodatniSloji->$dodatniSlojNaziv->vrsta =
                                VrstaDodatnegaSloja::from($config->dodatniSloji->$dodatniSlojNaziv->vrsta);
                            $this->dodatniSloji[$dodatniSlojNaziv] = $config->dodatniSloji->$dodatniSlojNaziv;
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
        foreach ($this->dodatniSloji as $dodatniSloj) {
            if ($dodatniSloj->vrsta == VrstaDodatnegaSloja::Pritrjen) {
                $this->povrsinskaMasa += $dodatniSloj->povrsinskaMasa;
            }
        }

        $this->Rw = 37.5 * log10($this->povrsinskaMasa) - 42;
        $this->C = $this->povrsinskaMasa > 100 ? -2 : -1;
        $this->Ctr = 16 - 9 * log10($this->povrsinskaMasa);
        if ($this->Ctr > -1) {
            $this->Ctr = -1;
        }
        if ($this->Ctr < -7) {
            $this->Ctr = -7;
        }

        foreach ($this->dodatniSloji as $dodatniSloj) {
            switch ($dodatniSloj->vrsta) {
                case VrstaDodatnegaSloja::Elasticen:
                    $this->dRw[] = $dodatniSloj->vrsta->dRw(
                        povrsinskaMasaKonstrukcije: $this->povrsinskaMasa,
                        RwKonstrukcije: $this->Rw,
                        povrsinskaMasaSloja: $dodatniSloj->povrsinskaMasa,
                        dinamicnaTogost: $dodatniSloj->dinamicnaTogost
                    );
                    break;
                case VrstaDodatnegaSloja::Nepritrjen:
                    $this->dRw[] = $dodatniSloj->vrsta->dRw(
                        povrsinskaMasaKonstrukcije: $this->povrsinskaMasa,
                        RwKonstrukcije: $this->Rw,
                        povrsinskaMasaSloja: $dodatniSloj->povrsinskaMasa,
                        sirinaMedprostora: $dodatniSloj->sirinaMedprostora
                    );
                    break;
            }
        }
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $konstrukcija = new \stdClass();

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if ($prop->isInitialized($this)) {
                if ($prop->getName() == 'ovoj') {
                } else {
                    $konstrukcija->{$prop->getName()} = $prop->getValue($this);
                }
            }
        }

        return $konstrukcija;
    }
}
