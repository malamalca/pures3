<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZunanjiHrup;

use App\Core\Log;
use App\Lib\EvalMath;

class Prostor
{
    public string $id;
    public string $naziv;

    public float $odmevniCas = 0;
    public float $prostornina = 0;

    public float $Af = 0;

    /**
     * @var float $Sf Površina vseh fasad
     */
    public float $Sf = 0;
    public float $minRw = 0;
    public float $Rw = 0;

    public float $Lzunaj = 60;
    public float $Lmax = 30;

    public array $fasade;
    private \stdClass $elementi;

    private array $options;

    /**
     * Class Constructor
     *
     * @param \stdClass|null $elementi Seznam elementov
     * @param \stdClass|string $config Configuration
     * @param array $options Možnosti izračuna
     * @return void
     */
    public function __construct($elementi = null, $config = null, $options = [])
    {
        $this->options = $options;

        if ($elementi) {
            $this->elementi = $elementi;
        } else {
            $this->elementi = new \stdClass();
            $this->elementi->konstrukcije = [];
            $this->elementi->odprtine = [];
            $this->elementi->maliElementi = [];
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
            switch ($prop->getName()) {
                case 'fasade':
                    if (!empty($config->fasade)) {
                        foreach ($config->fasade as $fasadaConfig) {
                            $fasada = new Fasada($this->elementi, $fasadaConfig);
                            $this->Sf += $fasada->povrsina;
                            $this->fasade[] = $fasada;
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
     * @param \stdClass $splosniPodatki Splošni podatki
     * @return void
     */
    public function analiza($splosniPodatki)
    {
        Log::info(sprintf('"%s": Začetek analize zunanjega hrupa - prostor', $this->id));

        $this->Af = 0.163 * $this->prostornina / $this->odmevniCas;

        $this->Sf = 0;
        $sumTau = 0;
        foreach ($this->fasade as $fasada) {
            $this->Sf += $fasada->povrsina;
            $sumTau += $fasada->povrsina / $this->Sf * pow(10, -$fasada->Rw / 10);
        }
        $this->Rw = -10 * log10($sumTau);

        // minimalna izolativnost konstrukcij, da se doseže nivo hrupa
        $this->minRw = $this->Lzunaj - $this->Lmax + 10 * log10($this->Sf / $this->Af);

        Log::info(sprintf('"%s": Konec analize zunanjega hrupa - prostor', $this->id));
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $prostor = new \stdClass();

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if ($prop->isInitialized($this)) {
                if ($prop->getName() == 'fasade') {
                    $prostor->fasade = [];
                    foreach ($this->fasade as $fasada) {
                        $prostor->fasade[] = $fasada->export();
                    }
                } else {
                    $prostor->{$prop->getName()} = $prop->getValue($this);
                }
            }
        }

        return $prostor;
    }
}
