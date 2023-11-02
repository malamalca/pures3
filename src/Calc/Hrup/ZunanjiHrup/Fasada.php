<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZunanjiHrup;

use App\Calc\Hrup\Elementi\Konstrukcija;
use App\Calc\Hrup\Elementi\OknaVrata;
use App\Calc\Hrup\ZunanjiHrup\Izbire\KoeficientStropa;
use App\Calc\Hrup\ZunanjiHrup\Izbire\OblikaFasade;
use App\Calc\Hrup\ZunanjiHrup\Izbire\VisinaLinijePogleda;
use App\Lib\EvalMath;

class Fasada
{
    public float $Rw = 0;
    public float $dRw_fasada = 0;
    public float $povrsina = 0;
    public bool $vplivPrometa = false;

    public OblikaFasade $oblikaFasade;
    public ?KoeficientStropa $koeficientStropa;
    public ?VisinaLinijePogleda $visinaLinijePogleda;

    public array $konstrukcije = [];
    public array $oknaVrata = [];
    private array $maliElementi = [];
    private array $options;

    private \stdClass $konstrukcijeLib;

    /**
     * Class Constructor
     *
     * @param \stdClass|null $konstrukcijeLib Seznam konstrukcij znotraj projekta (knjižnica)
     * @param \stdClass|string $config Configuration
     * @param array $options Možnosti izračuna
     * @return void
     */
    public function __construct($konstrukcijeLib = null, $config = null, $options = [])
    {
        $this->options = $options;
        $this->konstrukcijeLib = $konstrukcijeLib;

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
                case 'konstrukcije':
                    foreach ($config->konstrukcije as $konstrukcijaConfig) {
                        $libKonstrukcija = array_first(
                            $this->konstrukcijeLib->konstrukcije,
                            fn($kons) => $konstrukcijaConfig->idKonstrukcije == $kons->id
                        );
                        if (!$libKonstrukcija) {
                            throw new \Exception(sprintf(
                                'Konstrukcija "%s" v knjižnici ne obstaja.',
                                $konstrukcijaConfig->idKonstrukcije
                            ));
                        }
                        $konstrukcija = new ZunanjaKonstrukcija(
                            new Konstrukcija($libKonstrukcija),
                            $konstrukcijaConfig
                        );
                        $this->konstrukcije[] = $konstrukcija;
                        $this->povrsina += $konstrukcija->povrsina * $konstrukcija->stevilo;
                    }
                    break;
                case 'oknaVrata':
                    foreach ($config->oknaVrata as $oknaVrataConfig) {
                        $libOknaVrata = array_first(
                            $this->konstrukcijeLib->oknaVrata,
                            fn($ov) => $oknaVrataConfig->idOknaVrata == $ov->id
                        );
                        if (!$libOknaVrata) {
                            throw new \Exception(sprintf(
                                'Okno ali vrata "%s" v knjižnici ne obstaja.',
                                $oknaVrataConfig->idOknaVrata
                            ));
                        }
                        $oknaVrata = new ZunanjaOknaVrata(new OknaVrata($libOknaVrata), $oknaVrataConfig);
                        $this->oknaVrata[] = $oknaVrata;
                        $this->povrsina += $oknaVrata->povrsina * $oknaVrata->stevilo;
                    }
                    break;
                case 'oblikaFasade':
                    $this->oblikaFasade = OblikaFasade::from($config->oblikaFasade ?? 'ravna');
                    break;
                case 'koeficientStropa':
                    $this->koeficientStropa = KoeficientStropa::from($config->koeficientStropa);
                    break;
                case 'visinaLinijePogleda':
                    $this->visinaLinijePogleda = VisinaLinijePogleda::from($config->visinaLinijePogleda);
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
        $this->Rw = 0;
        $sumTau = 0;
        foreach ($this->konstrukcije as $konstrukcija) {
            $Rw = $konstrukcija->Rw + ($this->vplivPrometa ? $konstrukcija->Ctr : $konstrukcija->C);

            $sumTau += $konstrukcija->povrsina * $konstrukcija->stevilo / $this->povrsina * pow(10, -$Rw / 10) *
                $konstrukcija->stevilo;
        }
        foreach ($this->oknaVrata as $oknaVrata) {
            $Rw = $oknaVrata->Rw + ($this->vplivPrometa ? $oknaVrata->Ctr : $oknaVrata->C);

            $sumTau += $oknaVrata->povrsina * $oknaVrata->stevilo / $this->povrsina * pow(10, -$Rw / 10) *
                $oknaVrata->stevilo;
        }

        $this->Rw = -10 * log10($sumTau);
        $this->dRw_fasada = $this->oblikaFasade->faktorOblike(
            $this->koeficientStropa ?? null,
            $this->visinaLinijePogleda ?? null
        );
        $this->Rw = $this->Rw + $this->dRw_fasada;
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $fasada = new \stdClass();

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if ($prop->isInitialized($this)) {
                if ($prop->getName() == 'konstrukcije') {
                    $fasada->konstrukcije = [];
                    foreach ($this->konstrukcije as $konstrukcija) {
                        $fasada->konstrukcije[] = $konstrukcija->export();
                    }
                } else {
                    $fasada->{$prop->getName()} = $prop->getValue($this);
                }
            }
        }

        return $fasada;
    }
}
