<?php
declare(strict_types=1);

namespace App\Calc\Hrup\Elementi;

use App\Calc\Hrup\Elementi\Izbire\VrstaDodatnegaSloja;
use App\Lib\EvalMath;

class EnostavnaKonstrukcija
{
    public string $id;
    public string $naziv;
    public ?string $tip;
    public float $povrsinskaMasa = 0;
    public float $Rw = 0;
    public float $dR = 0;
    public ?float $dLw;
    public float $C = 0;
    public float $Ctr = 0;
    public float $Lnw = 0;

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
                    if (isset($config->dodatniSloji)) {
                        foreach ($config->dodatniSloji as $dodatniSloj) {
                            if (isset($dodatniSloj->povrsinskaMasa) && is_string($dodatniSloj->povrsinskaMasa)) {
                                $dodatniSloj->povrsinskaMasa = (float)$EvalMath->e($dodatniSloj->povrsinskaMasa);
                            }
                            if (is_string($dodatniSloj->vrsta)) {
                                $dodatniSloj->vrsta = VrstaDodatnegaSloja::from($dodatniSloj->vrsta);
                            }
                            $this->dodatniSloji[] = $dodatniSloj;
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

        if (empty($this->Rw)) {
            $this->Rw = round(37.5 * log10($this->povrsinskaMasa) - 42, 0);
        }
        if (empty($this->C)) {
            $this->C = $this->povrsinskaMasa > 200 ? -2 : -1;
        }
        if (empty($this->Ctr)) {
            $this->Ctr = round(16 - 9 * log10($this->povrsinskaMasa), 0);
            if ($this->Ctr > -1) {
                $this->Ctr = -1;
            }
            if ($this->Ctr < -7) {
                $this->Ctr = -7;
            }
        }

        if (empty($this->Lnw)) {
            $this->Lnw = 164 - 35 * log10($this->povrsinskaMasa);
        }

        foreach ($this->dodatniSloji as $dodatniSloj) {
            if (!isset($dodatniSloj->dR)) {
                switch ($dodatniSloj->vrsta) {
                    case VrstaDodatnegaSloja::Elasticen:
                        $dodatniSloj->dR = $dodatniSloj->vrsta->dR(
                            povrsinskaMasaKonstrukcije: $this->povrsinskaMasa,
                            RwKonstrukcije: $this->Rw,
                            povrsinskaMasaSloja: $dodatniSloj->povrsinskaMasa,
                            dinamicnaTogost: $dodatniSloj->dinamicnaTogost
                        );
                        break;
                    case VrstaDodatnegaSloja::Nepritrjen:
                        $dodatniSloj->dR = $dodatniSloj->vrsta->dR(
                            povrsinskaMasaKonstrukcije: $this->povrsinskaMasa,
                            RwKonstrukcije: $this->Rw,
                            povrsinskaMasaSloja: $dodatniSloj->povrsinskaMasa,
                            sirinaMedprostora: $dodatniSloj->sirinaMedprostora
                        );
                        break;
                    default:
                        $dodatniSloj->dR = 0;
                }
            }
            $this->dR += $dodatniSloj->dR ?? 0;
            if (!empty($dodatniSloj->dLw)) {
                $this->dLw = $dodatniSloj->dLw;
            }
        }

        //$this->Rw += $this->dR;
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
                if ($prop->getName() == 'dodatniSloji') {
                    $konstrukcija->dodatniSloji = [];
                    foreach ($prop->getValue($this) as $dodatniSloj) {
                        $dodatniSloj->vrsta = (string)$dodatniSloj->vrsta->value;
                        $konstrukcija->dodatniSloji[] = $dodatniSloj;
                    }
                } else {
                    $konstrukcija->{$prop->getName()} = $prop->getValue($this);
                }
            }
        }

        return $konstrukcija;
    }
}
