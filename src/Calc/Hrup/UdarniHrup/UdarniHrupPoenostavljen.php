<?php
declare(strict_types=1);

namespace App\Calc\Hrup\UdarniHrup;

use App\Lib\EvalMath;

class UdarniHrupPoenostavljen
{
    public string $id = '';
    public string $naziv = '';
    public ?string $idDodatnegaSloja;

    public float $Lnweq = 0;
    public float $Lnw = 0;
    public float $Lntw = 0;

    public float $minLnw = 0;

    public float $f0 = 0;
    public float $deltaL = 0;
    public float $K = 0;

    public float $prostorninaSprejemnegaProstora = 0;
    public float $povrsinskaMasaStranskihElementov = 0;

    public ?\stdClass $konstrukcija;
    private array $konstrukcijeLib;

    private array $options = [];

    private array $KLibMDodatnegaSloja = [
        100 => 0, 150 => 1, 200 => 2, 250 => 3, 300 => 4, 350 => 5, 400 => 6, 450 => 7, 500 => 8,
    ];
    private array $KLib = [
        100 => [1, 0, 0, 0, 0, 0, 0, 0, 0],
        150 => [1, 1, 0, 0, 0, 0, 0, 0, 0],
        200 => [2, 1, 1, 0, 0, 0, 0, 0, 0],
        250 => [2, 1, 1, 1, 0, 0, 0, 0, 0],
        300 => [3, 2, 1, 1, 1, 0, 0, 0, 0],
        350 => [3, 2, 1, 1, 1, 1, 0, 0, 0],
        400 => [4, 2, 2, 1, 1, 1, 1, 0, 0],
        450 => [4, 3, 2, 2, 1, 1, 1, 1, 1],
        500 => [4, 3, 2, 2, 1, 1, 1, 1, 1],
        600 => [5, 4, 3, 2, 2, 1, 1, 1, 1],
        700 => [5, 4, 3, 3, 2, 2, 1, 1, 1],
        800 => [6, 4, 4, 3, 2, 2, 2, 1, 1],
        900 => [6, 5, 4, 3, 3, 2, 2, 2, 2],
    ];

    /**
     * Class Constructor
     *
     * @param array $konstrukcijeLib Seznam konstrukcij znotraj projekta (knjižnica)
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
                case 'konstrukcija':
                    $this->konstrukcija = array_first(
                        $this->konstrukcijeLib,
                        fn($k) => $k->id == $config->idKonstrukcije
                    );
                    if (!$this->konstrukcija) {
                        throw new \Exception(sprintf(
                            'Ločilna konstrukcija za izračun udarnega hrupa "%s" v knjižnici ne obstaja.',
                            $config->idKonstrukcije
                        ));
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
        $this->Lnweq = $this->konstrukcija->Lnw;

        $dodatniSloj = null;
        if (!empty($this->idDodatnegaSloja)) {
            $dodatniSloj = array_first(
                $this->konstrukcija->dodatniSloji,
                fn($ds) => (!empty($ds->id) && $ds->id == $this->idDodatnegaSloja)
            );
        }
        if ($dodatniSloj) {
            $this->f0 = 160 * sqrt($dodatniSloj->dinamicnaTogost / $dodatniSloj->povrsinskaMasa);

            $fqs = [100, 125, 160, 200, 250, 315, 400, 500, 630, 800, 1000, 1200, 1600, 2000, 2500, 3150];
            $refFloor = [67, 67.5, 68, 68.5, 69, 69.5, 70, 70.5, 71, 71, 72, 72, 72, 72, 72, 72];
            $refDelta = [62, 62, 62, 62, 62, 62, 61, 60, 59, 58, 57, 54, 51, 48, 45, 42];

            if (isset($dodatniSloj->dLw)) {
                $this->deltaL = $dodatniSloj->dLw;
            } else {
                $dL = [];
                foreach ($fqs as $fq) {
                    $dL[] = 30 * log10($fq / $this->f0);
                }
                $devDelta = array_subtract_values($refFloor, $dL);

                $startDeviation = -20;
                $sumDeviation = 100;
                $prevSumDeviation = 0;
                while ($sumDeviation > 32 && $startDeviation < 20) {
                    // prestavim celoten spekter za $startDeviation
                    $refDeltaDev = array_map(fn($fq) => $fq + $startDeviation, $refDelta);
                    // odštejem delte od vrednosti referenčne konstrukcije, da dobim deviacije
                    $refDeviate = array_subtract_values($devDelta, $refDeltaDev);
                    // tiste deviacije, ki so manjše kot nič nastavim na 0 in seštejem
                    $refDevSum = array_map(fn($dev) => $dev < 0 ? 0 : $dev, $refDeviate);
                    $sumDeviation = array_sum($refDevSum);

                    $startDeviation += 1;
                }
                $this->deltaL = 78 - $refDeltaDev[7];
            }
        }

        // korekcija stranskega prenosa
        $this->K = 0;
        if ($this->povrsinskaMasaStranskihElementov > 0) {
            $nearestMStranski = array_nearest(
                array_keys($this->KLibMDodatnegaSloja),
                $this->povrsinskaMasaStranskihElementov
            );
            $nearestMLocilni = array_nearest(array_keys($this->KLib), $this->konstrukcija->povrsinskaMasa);

            $this->K = $this->KLib[$nearestMLocilni][$this->KLibMDodatnegaSloja[$nearestMStranski]];
        }

        // zaokroževanje Lnweq je po standardu
        $this->Lnw = round($this->Lnweq) - $this->deltaL + $this->K;

        $this->Lntw = $this->Lnw - 10 * log10($this->prostorninaSprejemnegaProstora / 30);
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
                //if ($prop->getName() == 'konstrukcije') {
                //
                //} else {
                    $fasada->{$prop->getName()} = $prop->getValue($this);
                //}
            }
        }

        return $fasada;
    }
}
