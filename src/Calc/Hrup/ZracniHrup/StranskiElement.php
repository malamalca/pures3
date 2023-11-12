<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZracniHrup;

use App\Calc\Hrup\Elementi\Konstrukcija;
use App\Calc\Hrup\ZracniHrup\Izbire\VrstaSpoja;
use App\Lib\Calc;
use App\Lib\EvalMath;

class StranskiElement
{
    public string $idKonstrukcije;

    public float $povrsina = 0;
    public float $izvornaPovrsina = 0;
    public float $oddajnaPovrsina = 0;
    public float $dolzinaSpoja = 0;

    public float $M = 0;

    public VrstaSpoja $vrstaSpoja;

    public ?float $Kmin_Df;
    public ?float $Kmin_Ff;
    public ?float $Kmin_Fd;

    public ?float $K_Df;
    public ?float $K_Ff;
    public ?float $K_Fd;

    public ?float $R_Df;
    public ?float $R_Ff;
    public ?float $R_Fd;

    /**
     * Indexi stranskega prenosa
     *
     * @var \stdClass $elementi
     */
    public \stdClass $pozicijeElementov;

    private array $options = [];

    /**
     * @var \App\Calc\Hrup\Elementi\Konstrukcija $izvornaKonstrukcija
     */
    public Konstrukcija $izvornaKonstrukcija;

    /**
     * @var \App\Calc\Hrup\Elementi\Konstrukcija $oddajnaKonstrukcija
     */
    public Konstrukcija $oddajnaKonstrukcija;

    /**
     * @var\App\Calc\Hrup\ZracniHrup\LocilniElement $locilniElement
     */
    private LocilniElement $locilniElement;

    /**
     * Class Constructor
     *
     * @param \App\Calc\Hrup\Elementi\Konstrukcija $izvornaKonstrukcija Konstrukcija iz knjižnice
     * @param \App\Calc\Hrup\Elementi\Konstrukcija $oddajnaKonstrukcija Konstrukcija iz knjižnice
     * @param \App\Calc\Hrup\ZracniHrup\LocilniElement $locilniElement Ločilni element
     * @param \stdClass|string $config Configuration
     * @param array $options Možnosti izračuna
     * @return void
     */
    public function __construct(
        $izvornaKonstrukcija,
        $oddajnaKonstrukcija,
        $locilniElement,
        $config = null,
        $options = []
    ) {
        $this->izvornaKonstrukcija = $izvornaKonstrukcija;
        $this->oddajnaKonstrukcija = $oddajnaKonstrukcija;
        $this->locilniElement = $locilniElement;
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

        $EvalMath = EvalMath::getInstance(['decimalSeparator' => '.', 'thousandsSeparator' => '']);

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            switch ($prop->getName()) {
                case 'vrstaSpoja':
                    $this->vrstaSpoja = VrstaSpoja::from($config->vrstaSpoja);
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

        if (!empty($this->povrsina) && empty($this->izvornaPovrsina)) {
            $this->izvornaPovrsina = $this->povrsina;
        }
        if (!empty($this->povrsina) && empty($this->oddajnaPovrsina)) {
            $this->oddajnaPovrsina = $this->povrsina;
        }
    }

    /**
     * Glavna funkcija za analizo cone
     *
     * @return void
     */
    public function analiza()
    {
        $this->M = log10(
            $this->locilniElement->konstrukcija->povrsinskaMasa / $this->izvornaKonstrukcija->povrsinskaMasa
        );

        if (($this->locilniElement->povrsina > 0) && ($this->oddajnaPovrsina > 0)) {
            $this->Kmin_Df = 10 * log10($this->dolzinaSpoja *
                ((1 / $this->locilniElement->povrsina) + (1 / $this->oddajnaPovrsina)));
        }

        if (($this->izvornaPovrsina > 0) && ($this->oddajnaPovrsina > 0)) {
            $this->Kmin_Ff = 10 * log10($this->dolzinaSpoja *
                ((1 / $this->izvornaPovrsina) + 1 / $this->oddajnaPovrsina));
        }

        if (($this->izvornaPovrsina > 0) && ($this->locilniElement->povrsina > 0)) {
            $this->Kmin_Fd = 10 * log10($this->dolzinaSpoja *
                ((1 / $this->izvornaPovrsina) + 1 / $this->locilniElement->povrsina));
        }

        $faktorjiK = $this->vrstaSpoja->faktorjiK($this->M);

        $indexDf = $this->pozicijeElementov->locilni > $this->pozicijeElementov->oddajni ?
            $this->pozicijeElementov->oddajni . $this->pozicijeElementov->locilni :
            $this->pozicijeElementov->locilni . $this->pozicijeElementov->oddajni;
        $this->K_Df = $faktorjiK['K' . $indexDf];

        $indexFf = $this->pozicijeElementov->izvorni > $this->pozicijeElementov->oddajni ?
            $this->pozicijeElementov->oddajni . $this->pozicijeElementov->izvorni :
            $this->pozicijeElementov->izvorni . $this->pozicijeElementov->oddajni;
        $this->K_Ff = $faktorjiK['K' . $indexFf];

        $indexFd = $this->pozicijeElementov->izvorni > $this->pozicijeElementov->locilni ?
            $this->pozicijeElementov->locilni . $this->pozicijeElementov->izvorni :
            $this->pozicijeElementov->izvorni . $this->pozicijeElementov->locilni;
        $this->K_Fd = $faktorjiK['K' . $indexFd];

        $this->R_Df = ($this->locilniElement->konstrukcija->Rw + $this->oddajnaKonstrukcija->Rw) / 2
            + Calc::combineDeltaR($this->locilniElement->konstrukcija, '', $this->oddajnaKonstrukcija, '')
            + $this->K_Df + 10 * log10($this->locilniElement->povrsina / $this->dolzinaSpoja);

        $this->R_Ff = ($this->izvornaKonstrukcija->Rw + $this->oddajnaKonstrukcija->Rw) / 2
            + Calc::combineDeltaR($this->izvornaKonstrukcija, '', $this->oddajnaKonstrukcija, '')
            + $this->K_Ff + 10 * log10($this->locilniElement->povrsina / $this->dolzinaSpoja);

        $this->R_Fd = ($this->izvornaKonstrukcija->Rw + $this->locilniElement->konstrukcija->Rw) / 2
            + Calc::combineDeltaR($this->izvornaKonstrukcija, '', $this->locilniElement->konstrukcija, '')
            + $this->K_Fd + 10 * log10($this->locilniElement->povrsina / $this->dolzinaSpoja);
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

                // pretvori enum v string
                if ($zunanjaKonstrukcija->{$prop->getName()} instanceof \UnitEnum) {
                    /* @phpstan-ignore-next-line */
                    $zunanjaKonstrukcija->{$prop->getName()} = $zunanjaKonstrukcija->{$prop->getName()}->value;
                }
            }
        }

        return $zunanjaKonstrukcija;
    }
}
