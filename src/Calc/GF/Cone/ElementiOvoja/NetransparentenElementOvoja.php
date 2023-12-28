<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\ElementiOvoja;

use App\Calc\GF\Cone\ElementiOvoja\Izbire\BarvaElementaOvoja;
use App\Calc\GF\Cone\ElementiOvoja\Izbire\VrstaTal;
use App\Core\Log;
use App\Lib\Calc;
use App\Lib\EvalMath;

class NetransparentenElementOvoja extends ElementOvoja
{
    public bool $protiZraku = true;

    public VrstaTal $tla;
    public BarvaElementaOvoja $barva;

    // lahko se overrida določilo iz TSG
    public ?bool $dobitekSS;

    // velja za konstrukcije v stiku z zemljino
    public ?float $obseg;
    public ?float $debelinaStene;
    public ?float $obodniPsi;
    public ?float $Lpi;
    public ?float $Lpe;
    public ?\stdClass $dodatnaIzolacija;

    // velja za vert. konstrukcije v stiku z zemljino
    public ?float $globina;
    public ?float $U_tla;

    // velja za tla proti neogrevani kleti
    public ?float $U_zid;
    public ?float $U_zid_nadTerenom;
    public ?float $visinaNadTerenom;
    public ?float $prostorninaKleti;
    public ?float $izmenjavaZraka;

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    protected function parseConfig($config)
    {
        parent::parseConfig($config);
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $EvalMath = EvalMath::getInstance(['decimalSeparator' => '.', 'thousandsSeparator' => '']);

        if (isset($config->protiZraku)) {
            $this->protiZraku = (bool)$config->protiZraku;
        } elseif (isset($this->konstrukcija->TSG->tip)) {
            $this->protiZraku = $this->konstrukcija->TSG->tip == 'zunanja';
        }
        $this->tla = VrstaTal::from($config->tla ?? 'pesek');

        $this->barva = BarvaElementaOvoja::from($config->barva ?? 'brez');

        if (isset($config->dobitekSS)) {
            $this->dobitekSS = $config->dobitekSS;
        }

        $dobitekSS = !empty($this->konstrukcija->TSG->dobitekSS);
        if (isset($this->dobitekSS)) {
            $dobitekSS = $this->dobitekSS;
        }
        if (empty($config->barva) && $dobitekSS) {
            Log::warn(sprintf('Netransparenten element ovoja "%s" nima določene barve.', $this->idKonstrukcije));
        }

        if (!empty($config->obseg)) {
            $obseg = $config->obseg;
            if (gettype($obseg) == 'string') {
                $obseg = (float)$EvalMath->e($obseg);
            }
            $this->obseg = $obseg;
        }

        if (!empty($config->debelinaStene)) {
            $this->debelinaStene = $config->debelinaStene;
        }

        if (!empty($config->globina)) {
            $this->globina = $config->globina;
        }

        if (!empty($config->obodniPsi)) {
            $this->obodniPsi = $config->obodniPsi;
        }

        if (!empty($config->U_tla)) {
            $this->U_tla = $config->U_tla;
        }

        if (!empty($config->dodatnaIzolacija)) {
            $this->dodatnaIzolacija = $config->dodatnaIzolacija;
        }

        if (!empty($config->U_zid)) {
            $this->U_zid = $config->U_zid;
        }
        if (!empty($config->U_zid_nadTerenom)) {
            $this->U_zid_nadTerenom = $config->U_zid_nadTerenom;
        }
        if (!empty($config->visinaNadTerenom)) {
            $this->visinaNadTerenom = $config->visinaNadTerenom;
        }
        if (!empty($config->prostorninaKleti)) {
            $this->prostorninaKleti = $config->prostorninaKleti;
        }
        if (!empty($config->izmenjavaZraka)) {
            $this->izmenjavaZraka = $config->izmenjavaZraka;
        }
    }

    /**
     * Analiza elementa
     *
     * @param \App\Calc\GF\Cone\Cona $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public function analiza($cona, $okolje)
    {
        if (empty($this->konstrukcija)) {
            throw new \Exception(sprintf('Konstrukcija "%s" v ovoju ne obstaja.', $this->idKonstrukcije));
        }
        if (empty($this->konstrukcija->TSG)) {
            throw new \Exception(sprintf('TSG podatki konstrukcije ovoja "%s" ne obstajajo.', $this->idKonstrukcije));
        }

        // temperaturni korekcijski faktor
        $this->b = empty($this->konstrukcija->ogrRazvodT) ? 1 :
            ($this->konstrukcija->ogrRazvodT - $okolje->projektnaZunanjaT) /
            ($cona->notranjaTOgrevanje - $okolje->projektnaZunanjaT);

        // napolni podatke o vplivu zemljine
        if ($this->konstrukcija->TSG->tip != 'zunanja') {
            $this->vplivZemljine();
        } else {
            // faktor sončnega sevanja
            foreach ($okolje->obsevanje as $line) {
                if ($line->orientacija == $this->orientacija && $line->naklon == $this->naklon) {
                    $this->soncnoObsevanje = $line->obsevanje;
                    break;
                }
            }
            if (empty($this->soncnoObsevanje)) {
                throw new \Exception(sprintf('Sončno obsevanje za element %s ne obstaja', $this->opis));
            }

            $this->H_ogrevanje = ($this->U + $cona->deltaPsi) * $this->povrsina * $this->b * $this->stevilo;
            $this->H_hlajenje = ($this->U + $cona->deltaPsi) * $this->povrsina * $this->b * $this->stevilo;
        }

        // izračun dodatnih temperatura
        $temperature = $this->osnovniTemperaturniPodatki($cona, $okolje);

        // transmisijske toplotne izgube za ogrevanje in hlajenje Qtr,m (kWh/m)
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            if ($this->konstrukcija->TSG->tip == 'zunanja') {
                // konstrukcija proti zraku
                $this->transIzgubeOgrevanje[$mesec] = $this->H_ogrevanje * 24 / 1000 *
                    $cona->deltaTOgrevanje[$mesec] * $stDni * $this->stevilo;

                $this->transIzgubeHlajenje[$mesec] = $this->H_hlajenje * 24 / 1000 *
                    $cona->deltaTHlajenje[$mesec] * $stDni * $this->stevilo;

                // svetla barva 0.3, srednja barva 0.6, temna barva 0.9
                $alphaSr = !empty($this->options['referencnaStavba']) ? 0.5 : $this->barva->koeficientAlphaSr();
                $Fsky = $this->naklon < 45 ? 1 : 0.5;
                $hri = 4.14;
                $dTsky = 11;

                // toplotni tok zaradi osončenja
                // ISO 52016-1:2017 enačba 124 na strani 105
                $Qsol = $alphaSr * $this->konstrukcija->Rse * ($this->U + $cona->deltaPsi) * $this->povrsina *
                    $this->faktorSencenja[$mesec] * $this->soncnoObsevanje[$mesec] * $stDni;

                // sevanje elementa proti nebu za trenutni mesec
                // popravek po verziji v160:
                // $Qsky = 0.001 * $Fsky * $this->konstrukcija->Rse * ($this->U + $cona->deltaPsi) *
                $Qsky = $Fsky * $this->konstrukcija->Rse * ($this->U + $cona->deltaPsi) *
                    $this->povrsina * $hri * $dTsky * $stDni * 24;

                if (!empty($this->konstrukcija->TSG->dobitekSS) || (isset($this->dobitekSS) && $this->dobitekSS)) {
                    $this->solarniDobitkiOgrevanje[$mesec] = ($Qsol - $Qsky) / 1000 * $this->stevilo;
                    $this->solarniDobitkiHlajenje[$mesec] = ($Qsol - $Qsky) / 1000 * $this->stevilo;
                } else {
                    $this->solarniDobitkiOgrevanje[$mesec] = 0;
                    $this->solarniDobitkiHlajenje[$mesec] = 0;
                }
            } else {
                // konstrukcija proti zemljini
                // TODO: stene, ostale horizontale

                // toplotni tok proti tlom
                $this->izracunTokaProtiZemljini($mesec, $okolje, $temperature);

                $this->solarniDobitkiOgrevanje[$mesec] = 0;
                $this->solarniDobitkiHlajenje[$mesec] = 0;
            }
        }

        if ($this->konstrukcija->TSG->tip != 'zunanja') {
            $this->H_ogrevanje = $this->H_ogrevanje / $temperature['stMesecevOgrevanja'] /
                ($temperature['povprecnaTOgrevanja'] - $okolje->povprecnaLetnaTemp);

            $this->H_hlajenje = $this->H_hlajenje / (12 - $temperature['stMesecevOgrevanja']) /
                ($temperature['povprecnaTHlajenja'] - $okolje->povprecnaLetnaTemp);
        }
    }

    /**
     * Doda podatke o zemljini na konstrukcijo
     *
     * @return void
     */
    private function vplivZemljine()
    {
        // proti terenu
        if ($this->konstrukcija->TSG->tip == 'tla-teren') {
            $B = $this->povrsina / (0.5 * $this->obseg);

            // ekvivalentna debelina konstrukcije - TLA
            $dt = $this->debelinaStene + $this->tla->lambda() * 1 / $this->konstrukcija->U;

            if ($dt < $B) {
                // neizolirana ali srednje izolirana tla
                $this->U = 2 * $this->tla->lambda() / (Pi() * $B + $dt + 0.5 * ($this->globina ?? 0)) *
                    log(Pi() * $B / ($dt + 0.5 * ($this->globina ?? 0)) + 1);
            } else {
                // dobro izolirana tla
                $this->U = $this->tla->lambda() / (0.457 * $B + $dt + 0.5 * ($this->globina ?? 0));
            }

            $d_ = 0;
            if (!empty($this->dodatnaIzolacija)) {
                $Rn = $this->dodatnaIzolacija->debelina / $this->dodatnaIzolacija->lambda;
                $R_ = $Rn - $this->dodatnaIzolacija->debelina / $this->tla->lambda();
                $d_ = $R_ * $this->tla->lambda();

                if ($this->dodatnaIzolacija->tip == 'horizontalna') {
                    $horizontalniPsi = -$this->tla->lambda() / Pi() * (
                        log($this->dodatnaIzolacija->dolzina / $dt + 1) -
                        log($this->dodatnaIzolacija->dolzina / ($dt + $d_) + 1)
                    );

                    $this->U = $this->U + 2 * $horizontalniPsi / $B;
                } elseif ($this->dodatnaIzolacija->tip == 'vertikalna') {
                    $this->obodniPsi = -$this->tla->lambda() / Pi() * (
                        log(2 * $this->dodatnaIzolacija->dolzina / $dt + 1) -
                        log(2 * $this->dodatnaIzolacija->dolzina / ($dt + $d_) + 1)
                    );
                }
            }

            // periodic heat transfer coefficients Annex H
            // variacija notranje temperature
            // ISO 13370, C.3.1
            $this->Lpi = $this->povrsina * $this->tla->lambda() / $dt *
                sqrt(2 / (pow(1 + $this->tla->sigma() / $dt, 2) + 1));/* +
                ($this->globina ?? 0) * $this->obseg * $this->tla->lambda() / $dw *
                sqrt(2 / (pow(1 + $this->tla->sigma() / $dw, 2) + 1));*/

            if (!empty($this->dodatnaIzolacija)) {
                if ($this->dodatnaIzolacija->tip == 'horizontalna') {
                    // horizontalna izolacija po obodu
                    // ISO 13370, C.3.6
                    $this->Lpe = 0.37 * $this->obseg * $this->tla->lambda() * (
                        (
                            1 - exp(-$this->dodatnaIzolacija->dolzina / $this->tla->sigma())) *
                            log($this->tla->sigma() / ($dt + $d_) + 1)
                        +
                        (
                            exp(-$this->dodatnaIzolacija->dolzina / $this->tla->sigma()) *
                            log($this->tla->sigma() / $dt + 1)
                        )
                    );
                } elseif ($this->dodatnaIzolacija->tip == 'vertikalna') {
                    // vertikalna izolacija po vertikali oboda
                    // ISO 13370, C.3.7
                    $this->Lpe = 0.37 * $this->obseg * $this->tla->lambda() * (
                        (1 - exp(-2 * $this->dodatnaIzolacija->dolzina / $this->tla->sigma())) *
                        log($this->tla->sigma() / ($dt + $d_) + 1) +
                        (
                            exp(-2 * $this->dodatnaIzolacija->dolzina / $this->tla->sigma()) *
                            log($this->tla->sigma() / $dt + 1)
                        )
                    );
                }
            } else {
                // tla na terenu brez obodne izolacije, neizolirana tla, vkopana tla
                if (!empty($this->globina)) {
                    // tla vkopane kleti
                    // =0,37*U!B31*C33*                                              (EXP(-U!B33/E33)    *LN((E33/F82)+1))
                    $this->Lpe = 0.37 * $this->obseg * $this->tla->lambda() *
                        exp(-$this->globina / $this->tla->sigma()) * log($this->tla->sigma() / $dt + 1);
                } else {
                    //=0,37*U!B31*C33*((1-EXP(-C45*U!B39/E33))*LN((E33/(F82+F87))+1)+EXP(-C45*U!B39/E33)*LN((E33/F82)+1))
                    $this->Lpe = 0.37 * $this->obseg * $this->tla->lambda() *
                        ((1 - exp(-0 / $this->tla->sigma())) *
                        log($this->tla->sigma() / ($dt + $d_) + 1) +
                        (exp(-0 / $this->tla->sigma()) *
                        log($this->tla->sigma() / $dt + 1)));
                }
            }
        }

        if ($this->konstrukcija->TSG->tip == 'stena-teren') {
            // ekvivalentna debelina stene
            // enačba 15 v standardu; TODO: v standardu je brez debelineStene, XLS pa jo upošteva
            $d_wb = $this->debelinaStene + $this->tla->lambda() * 1 / $this->konstrukcija->U;

            // ekvivalentna debelina tal (floor)
            // enačba 12 v standard
            $d_f = $this->debelinaStene + $this->tla->lambda() * 1 / $this->U_tla;
            //$d_f = 9.7;

            if ($d_wb < $d_f) {
                $d_f = $d_wb;
            }

            $z = $this->globina ?? 0;

            /** enačba 16 v standardu */
            $this->U = 2 * $this->tla->lambda() / (Pi() * $z) *
                (1 + 0.5 * $d_f / ($d_f + $z)) * log($z / $d_wb + 1);

            // enačba H.8 v standardu, SAMO DESNI DEL ZA STENE
            $this->Lpi = $this->obseg * $this->globina *
                $this->tla->lambda() / $d_wb *
                sqrt(2 / (pow(1 + $this->tla->sigma() / $d_wb, 2) + 1));

            // enačba H.9 v standardu, SAMO DESNI DEL ZA STENE
            $this->Lpe = 0.37 * $this->obseg * $this->tla->lambda() * (
                2 * (1 - exp(-$this->globina / $this->tla->sigma())) * log(($this->tla->sigma() / $d_wb) + 1)
            );
        }

        if (in_array($this->konstrukcija->TSG->tip, ['tla-neogrevano'])) {
            // ekvivalentna debelina tal (floor)
            // enačba 12 v standard
            $d_f = $this->debelinaStene + $this->tla->lambda() * 1 / $this->U_tla;

            /** enačba 16 v standardu */
            $this->U = 1 / (1 / $this->konstrukcija->U + $this->povrsina / (($this->povrsina * $this->U_tla) +
                ($this->visinaNadTerenom * $this->obseg * $this->U_zid_nadTerenom) +
                ($this->globina * $this->obseg * $this->U_zid) +
                (0.34 * $this->prostorninaKleti * $this->izmenjavaZraka)));

            $this->Lpi = pow(
                1 / ($this->povrsina * $this->konstrukcija->U) +
                1 / (
                    ($this->povrsina + $this->globina * $this->obseg) * $this->tla->lambda() / $this->tla->sigma() +
                    $this->visinaNadTerenom * $this->obseg * $this->U_zid_nadTerenom +
                    0.33 * $this->prostorninaKleti * $this->izmenjavaZraka
                ),
                -1
            );

            $this->Lpe = $this->povrsina * $this->konstrukcija->U * (
                    0.37 * $this->obseg * $this->tla->lambda() * (2 - exp(-$this->globina / $this->tla->sigma())) *
                    log($this->tla->sigma() / $d_f + 1) +
                    $this->visinaNadTerenom * $this->obseg * $this->U_zid_nadTerenom +
                    0.33 * $this->prostorninaKleti * $this->izmenjavaZraka
                ) / (
                    ($this->povrsina + $this->globina * $this->obseg) * $this->tla->lambda() / $this->tla->sigma() +
                    $this->visinaNadTerenom * $this->obseg * $this->U_zid_nadTerenom +
                    0.33 * $this->prostorninaKleti * $this->izmenjavaZraka +
                    $this->povrsina * $this->konstrukcija->U
                );
        }
    }

    /**
     * Izračun dodatnih temperaturnih podatkov
     *
     * @param \App\Calc\GF\Cone\Cona $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return array
     */
    private function osnovniTemperaturniPodatki($cona, $okolje)
    {
        $notranjaT = [];
        $zunanjaT = [];

        $povprecnaTOgrevanja = 0;
        $povprecnaTHlajenja = 0;
        $stMesecevOgrevanja = 0;
        foreach (array_keys(Calc::MESECI) as $mesec) {
            // določi temperaturo prostora
            if (Calc::jeMesecBrezOgrevanja($mesec)) {
                $notranjaT[$mesec] = $cona->notranjaTHlajenje;
                $povprecnaTHlajenja += $notranjaT[$mesec];
            } else {
                if (empty($this->konstrukcija->TSG->talnoGretje)) {
                    $notranjaT[$mesec] = $cona->notranjaTOgrevanje;
                } else {
                    $notranjaT[$mesec] = $okolje->zunanjaT[$mesec] +
                        $this->b * ($cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec]);
                }
                $povprecnaTOgrevanja += $notranjaT[$mesec];
                $stMesecevOgrevanja++;
            }
        }

        $povprecnaTHlajenja = $povprecnaTHlajenja / (12 - $stMesecevOgrevanja);
        $povprecnaTOgrevanja = $povprecnaTOgrevanja / $stMesecevOgrevanja;
        $povprecnaNotranjaT = ($povprecnaTOgrevanja + $povprecnaTHlajenja) / 2;

        return ['notranjaT' => $notranjaT, 'zunanjaT' => $zunanjaT, 'povprecnaTHlajenja' => $povprecnaTHlajenja,
            'povprecnaTOgrevanja' => $povprecnaTOgrevanja, 'povprecnaNotranjaT' => $povprecnaNotranjaT,
            'stMesecevOgrevanja' => $stMesecevOgrevanja,
        ];
    }

    /**
     * Izračun toka proti zemljini
     *
     * @param int $mesec Št. meseca
     * @param \stdClass $okolje Podatki okolja
     * @param array $temperature Dodatne temperature iz osnovniTemperaturniPodatki()
     * @return void
     */
    private function izracunTokaProtiZemljini($mesec, $okolje, $temperature)
    {
        extract($temperature);
        $povprecnaNotranjaT = $povprecnaNotranjaT ?? 20;
        $notranjaT = $notranjaT ?? 20;

        $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

        $urniToplotniTok = $this->U *
            $this->povrsina * ($povprecnaNotranjaT - $okolje->povprecnaLetnaTemp) +
            $this->obseg * ($this->obodniPsi ?? 0) * ($notranjaT[$mesec] - $okolje->zunanjaT[$mesec]) -
            $this->Lpi * ($povprecnaNotranjaT - $notranjaT[$mesec]) +
            $this->Lpe * ($okolje->povprecnaLetnaTemp - $okolje->zunanjaT[$mesec]);

        $this->transIzgubeOgrevanje[$mesec] = $urniToplotniTok * 24 * $stDni / 1000 * $this->stevilo;
        $this->transIzgubeHlajenje[$mesec] = $urniToplotniTok * 24 * $stDni / 1000 * $this->stevilo;

        if (Calc::jeMesecBrezOgrevanja($mesec)) {
            $this->H_hlajenje += $urniToplotniTok * $this->stevilo;
        } else {
            $this->H_ogrevanje += $urniToplotniTok * $this->stevilo;
        }
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $elementOvoja = parent::export();

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if ($prop->isInitialized($this)) {
                $elementOvoja->{$prop->getName()} = $prop->getValue($this);

                // pretvori enum v string
                if ($elementOvoja->{$prop->getName()} instanceof \UnitEnum) {
                    /* @phpstan-ignore-next-line */
                    $elementOvoja->{$prop->getName()} = $elementOvoja->{$prop->getName()}->value;
                }
            }
        }

        return $elementOvoja;
    }
}
