<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZracniHrup;

use App\Calc\Hrup\Elementi\EnostavnaKonstrukcija;
use App\Calc\Hrup\ZracniHrup\Izbire\VrstaSpoja;
use App\Lib\Calc;
use App\Lib\EvalMath;

class StranskiElementPoenostavljen
{
    public string $idKonstrukcije;
    public ?string $idDodatnegaSloja = null;
    public float $dolzinaSpoja = 0;

    /**
     * @var float $povrisna Oddajna površina
     */
    public float $povrsina = 0;

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
     * @var \App\Calc\Hrup\Elementi\EnostavnaKonstrukcija $konstrukcija
     */
    public EnostavnaKonstrukcija $konstrukcija;

    /**
     * @var \App\Calc\Hrup\ZracniHrup\LocilniElement $locilniElement
     */
    private LocilniElement $locilniElement;

    /**
     * Class Constructor
     *
     * @param \App\Calc\Hrup\Elementi\EnostavnaKonstrukcija $konstrukcija Konstrukcija iz knjižnice
     * @param \App\Calc\Hrup\ZracniHrup\LocilniElement $locilniElement Ločilni element
     * @param \stdClass|string $config Configuration
     * @param array $options Možnosti izračuna
     * @return void
     */
    public function __construct($konstrukcija, $locilniElement, $config = null, $options = [])
    {
        $this->konstrukcija = $konstrukcija;
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
    }

    /**
     * Glavna funkcija za analizo cone
     *
     * @return void
     */
    public function analiza()
    {
        $this->M = log10($this->locilniElement->konstrukcija->povrsinskaMasa / $this->konstrukcija->povrsinskaMasa);

        $this->Kmin_Df = 10 * log10($this->dolzinaSpoja *
            ((1 / $this->locilniElement->povrsina) + (1 / $this->povrsina)));

        $this->Kmin_Ff = 10 * log10($this->dolzinaSpoja *
            ((1 / $this->povrsina) + 1 / $this->povrsina));

        $this->Kmin_Fd = 10 * log10($this->dolzinaSpoja *
            ((1 / $this->povrsina) + 1 / $this->locilniElement->povrsina));

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

        $this->R_Df = ($this->locilniElement->konstrukcija->Rw + $this->konstrukcija->Rw) / 2
            + Calc::combineDeltaR(
                $this->locilniElement->konstrukcija,
                $this->locilniElement->idDodatnegaSloja1,
                $this->konstrukcija,
                $this->idDodatnegaSloja
            )
            + $this->K_Df + 10 * log10($this->locilniElement->povrsina / $this->dolzinaSpoja);

        $this->R_Ff = ($this->konstrukcija->Rw + $this->konstrukcija->Rw) / 2
            + Calc::combineDeltaR(
                $this->konstrukcija,
                $this->idDodatnegaSloja,
                $this->konstrukcija,
                $this->idDodatnegaSloja
            )
            + $this->K_Ff + 10 * log10($this->locilniElement->povrsina / $this->dolzinaSpoja);

        $this->R_Fd = ($this->konstrukcija->Rw + $this->locilniElement->konstrukcija->Rw) / 2
            + Calc::combineDeltaR(
                $this->konstrukcija,
                $this->idDodatnegaSloja,
                $this->locilniElement->konstrukcija,
                $this->locilniElement->idDodatnegaSloja2
            )
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
            if ($prop->isInitialized($this) && !is_null($prop->getValue($this))) {
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
