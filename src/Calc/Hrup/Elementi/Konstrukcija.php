<?php
declare(strict_types=1);

namespace App\Calc\Hrup\Elementi;

use App\Calc\Hrup\Elementi\Izbire\VrstaDodatnegaSloja;
use App\Lib\Calc;
use App\Lib\EvalMath;

class Konstrukcija
{
    private const HITROST_ZVOKA = 343;
    private const GOSTOTA_ZRAKA = 1.18;
    private const DOLZINA_ROBA_1 = 4;
    private const DOLZINA_ROBA_2 = 3;

    public string $id;
    public string $naziv;
    public string $tip = 'zahtevna';
    public string $racunskiPasovi = 'tercni';

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

        $this->debelina = $this->povrsinskaMasa / $this->gostota;
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

        //$E = 68000000000;  // young
        //$poi = 0.23; // poisson

        //$B = ($E * pow($this->debelina, 3)) / (12.0 * (1.0 - pow($poi, 2)));                        // Rigidez del material [Nm^2]
        //$fc = pow(self::HITROST_ZVOKA, 2) / (2 * pi()) * sqrt($this->povrsinskaMasa / $B);          // Frecuencia crítica [Hz]
        $f11 = pow(self::HITROST_ZVOKA, 2) / (4 * $fcrit) *
            ((1 / pow(self::DOLZINA_ROBA_1, 2)) + (1 / pow(self::DOLZINA_ROBA_2, 2)));

        //$fcrit = $fc;
        $fc = $fcrit;

        // izračun faktorja sevanja "radiation factor"
        foreach (Calc::FREKVENCE_TERCE as $fq) {
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
            //$f11 = pow(self::HITROST_ZVOKA, 2) / (4 * $fc) * (1 / pow(self::DOLZINA_ROBA_1, 2) +
            //    1 / pow(self::DOLZINA_ROBA_2, 2));

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
                if ($f11 > $fq && $sigma > $sigma2) {
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

        $this->Rw = Calc::izracunajRw($this->R);
        $this->C = Calc::izracunajC($this->R);
        $this->Ctr = Calc::izracunajCtr($this->R);

        // dodatni sloji
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
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $konstrukcija = new \stdClass();
        $konstrukcija->tip = 'zahtevna';

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
