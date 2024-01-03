<?php
declare(strict_types=1);

namespace App\Calc\Hrup\Elementi;

use App\Calc\Hrup\Elementi\Izbire\VrstaDodatnegaSloja;
use App\Lib\EvalMath;

class Konstrukcija
{
    //private const FQS = [100, 125, 160, 200, 250, 315, 400, 500, 630, 800, 1000, 1250, 1600, 2000, 2500];
    private const FQS = [
        50, 63, 80,
        100, 125, 160, 200, 250, 315, 400, 500, 630, 800, 1000, 1250, 1600, 2000, 2500,
        3150, 4000, 5000,
    ];
    //private const FQS = [63, 125, 250, 500, 1000, 2000, 4000];

    private const RF = [0, 0, 0, 33, 36, 39, 42, 45, 48, 51, 52, 53, 54, 55, 56, 56, 56, 56, 0, 0, 0];
    private const SPQ_C = [41, 37, 34, 30, 27, 24, 22, 20, 18, 16, 14, 13, 12, 11, 10, 10, 10, 10, 10, 10, 10];
    private const SPQ_CTR = [25, 23, 21, 20, 20, 18, 16, 15, 14, 13, 12, 11, 9, 8, 9, 10, 11, 13, 15, 16, 18];

    private const HITROST_ZVOKA = 344;
    private const GOSTOTA_ZRAKA = 1.18;
    private const DOLZINA_ROBA_1 = 4;
    private const DOLZINA_ROBA_2 = 3;

    public string $id;
    public string $naziv;
    public ?string $tip;

    public float $povrsinskaMasa = 0;
    public float $gostota = 0;
    public float $debelina = 0;
    public float $hitrostLongitudinalnihValov = 0;
    public float $faktorNotranjegaDusenja = 0;

    public array $R = [];
    public float $Rw = 0;
    public float $dR = 0;
    public ?float $dLw;
    public float $C = 0;
    public float $Ctr = 0;

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

        // kritična frekvenca
        $fcrit = pow(self::HITROST_ZVOKA, 2) / (1.8 * $this->hitrostLongitudinalnihValov * $this->debelina);

        // izračun faktorja sevanja "radiation factor"
        foreach (self::FQS as $fq) {
            // valovno število v radianih na m
            $k0 = 2 * pi() * $fq / self::HITROST_ZVOKA;

            // enačba B.2 spodaj
            $A = -0.964 - (0.5 + (self::DOLZINA_ROBA_2 / (pi() * self::DOLZINA_ROBA_1))) *
                log(self::DOLZINA_ROBA_2 / self::DOLZINA_ROBA_1) +
                (5 * self::DOLZINA_ROBA_2 / (2 * pi() * self::DOLZINA_ROBA_1)) -
                (1 / (4 * pi() * self::DOLZINA_ROBA_1 * self::DOLZINA_ROBA_2 * pow($k0, 2)));

            // enačba B.2
            $faktorSevanja = 0.5 * (log($k0 * sqrt(self::DOLZINA_ROBA_1 * self::DOLZINA_ROBA_2)) - $A);
            if ($faktorSevanja > 2) {
                $faktorSevanja = 2;
            }

            // frekvenca platoja
            $fp = $this->hitrostLongitudinalnihValov / (5.5 * $this->debelina);

            // Da bi upoštevali tudi druge vrste valov, ki so relevantni pri debelih stenah in/ali pri višjih frekvencah,
            // se nad kritično frekvenco ta frekvenca pri računu nadomesti z efektivno kritično frekvenco
            $fc = $fcrit;
            if ($fq > $fcrit && $fq < $fp) {
                $fc = $fcrit * (4.05 * ($this->debelina * $fq / $this->hitrostLongitudinalnihValov) +
                    sqrt(1 + (4.05 * $this->debelina * $fq / $this->hitrostLongitudinalnihValov)));
            } elseif ($fq > $fcrit && $fq >= $fp) {
                $fc = 2 * $fcrit * pow($fq / $fp, 3);
            }

            // SIGMA = faktor sevanja za proste upogibne valove
            // enačbe B.3a
            $sigma1 = 1 / sqrt(1 - $fc / $fq);

            $sigma2 = 4 * self::DOLZINA_ROBA_1 * self::DOLZINA_ROBA_2 * pow($fq / self::HITROST_ZVOKA, 2);
            $sigma3 =
                sqrt((2 * pi() * $fq * (self::DOLZINA_ROBA_1 + self::DOLZINA_ROBA_2)) / (16 * self::HITROST_ZVOKA));
            $f11 = pow(self::HITROST_ZVOKA, 2) / (4 * $fc) * (1 / pow(self::DOLZINA_ROBA_1, 2) +
                1 / pow(self::DOLZINA_ROBA_2, 2));

            // enačba B.3b posebej
            $lambda = sqrt($fq / $fc);

            $delta1 = (((1 - pow($lambda, 2)) * log((1 + $lambda) / (1 - $lambda)) + 2 * $lambda)) /
                (4 * pow(pi(), 2) * pow(1 - pow($lambda, 2), 1.5));
            if ($fq > $fc / 2) {
                $delta2 = 0;
            } else {
                $delta2 = (8 * pow(self::HITROST_ZVOKA, 2) * (1 - 2 * pow($lambda, 2))) /
                    (pow($fc, 2) * pow(pi(), 4) * self::DOLZINA_ROBA_1 * self::DOLZINA_ROBA_2 *
                    $lambda * sqrt(1 - pow($lambda, 2)));
            }

            $sigma4 = 2 * (self::DOLZINA_ROBA_1 + self::DOLZINA_ROBA_2) /
                (self::DOLZINA_ROBA_1 * self::DOLZINA_ROBA_2) *
                self::HITROST_ZVOKA / $fc * $delta1 + $delta2;

            if ($f11 <= $fc / 2) {
                if ($fq >= $fc) {
                    $sigma = $sigma1;
                } else {
                    $sigma = $sigma4;
                }
                if ($f11 > $fq & $sigma > $sigma2) {
                    $sigma = $sigma2;
                }
            } else {
                if ($fq < $fc && $sigma2 < $sigma3) {
                    $sigma = $sigma2;
                } elseif ($fq > $fc && $sigma1 < $sigma3) {
                    $sigma = $sigma1;
                } else {
                    $sigma = $sigma3;
                }
            }

            if ($sigma > 2) {
                $sigma = 2;
            }

            // Odmevni čas elementa

            // SKUPNI FAKTOR IZGUB
            // enačba C.5
            $ntot = $this->faktorNotranjegaDusenja + ($this->povrsinskaMasa / (485 * sqrt($fq)));

            // enačba C.4
            /*$X = sqrt(31.1 / $fcrit);
            $Y = 44.3 * ($fcrit / $this->povrsinskaMasa);

            // koeficient absorpcije pri spoju k celotne talne plošče
            $alpha = 1/3 * pow((2 * sqrt($X * $Y) * (1 + $X) * (1 + $Y)) / ($X * pow(1 + $Y, 2)+ 2 * $Y * (1 + pow($X, 2))), 2);
            $alphak = $alpha * (1 - 0.9999 * $alpha);

            $S = $this->debelina * self::DOLZINA_ROBA_1;

            // enačba C.1
            $ntot2 = $this->faktorNotranjegaDusenja + ((2 * self::GOSTOTA_ZRAKA * self::HITROST_ZVOKA * $sigma) /
                (2 * pi() * $fq * $this->povrsinskaMasa)) +
                (self::HITROST_ZVOKA / (pow(pi(), 2) * $S * sqrt($fq * $fc)) *
                (((2 * $this->debelina + 2 * self::DOLZINA_ROBA_1) * $alphak) * 2 +
                 ((2 * $this->debelina + 2 * self::DOLZINA_ROBA_2) * $alphak) * 2));*/

            // enačba B.1
            if ($fq > ($fc + 0.15 * $fc)) {
                $tau = (pow((2 * self::GOSTOTA_ZRAKA * self::HITROST_ZVOKA) /
                    (2 * pi() * $fq * $this->povrsinskaMasa), 2)) *
                    (pi() * $fc * pow($sigma, 2)) / (2 * $fq * $ntot);
            } elseif ($fq >= ($fc - 0.15 * $fc) && $fq <= ($fc + 0.1 * $fc)) {
                $tau = (pow((2 * self::GOSTOTA_ZRAKA * self::HITROST_ZVOKA) /
                    (2 * pi() * $fq * $this->povrsinskaMasa), 2)) *
                    pi() * pow($sigma, 2) / (2 * $ntot);
            } else {
                /*if ($fq < ($fc - 0.15 * $fc))*/
                $tau = (pow((2 * self::GOSTOTA_ZRAKA * self::HITROST_ZVOKA) /
                    (2 * pi() * $fq * $this->povrsinskaMasa), 2)) *
                    (2 * $faktorSevanja + (pow(self::DOLZINA_ROBA_1 + self::DOLZINA_ROBA_2, 2)) /
                    (pow(self::DOLZINA_ROBA_1, 2) + pow(self::DOLZINA_ROBA_2, 2)) *
                    sqrt($fc / $fq) * pow($sigma, 2) / $ntot);
            }

            $this->R[$fq] = -10 * log10($tau);
        }

        // 717-1
        $shift = -40;
        $shiftSum = 99;
        while ($shift < 40 && $shiftSum > 32) {
            $shiftSum = 0;
            foreach (self::FQS as $ix => $fq) {
                if (self::RF[$ix] <> 0 && $this->R[$fq] - (self::RF[$ix] + $shift) > 0) {
                    $shiftSum += $this->R[$fq] - (self::RF[$ix] + $shift);
                }
            }
            $shift++;
        }

        $this->Rw = 52 + $shift - 1;

        $sumTau = 0;
        foreach (self::FQS as $ix => $fq) {
            $sumTau += pow(10, (-self::SPQ_C[$ix] - $this->R[$fq]) / 10);
        }

        $this->C = $this->Rw - round((-10 * log10($sumTau)), 0);

        $sumTau = 0;
        foreach (self::FQS as $ix => $fq) {
            $sumTau += pow(10, (-self::SPQ_CTR[$ix] - $this->R[$fq]) / 10);
        }

        $this->Ctr = $this->Rw - round((-10 * log10($sumTau)), 0);
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
