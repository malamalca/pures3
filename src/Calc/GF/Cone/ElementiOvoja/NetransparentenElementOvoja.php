<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\ElementiOvoja;

use App\Core\Configure;
use App\Lib\Calc;

class NetransparentenElementOvoja extends ElementOvoja
{
    public bool $protiZraku = true;

    public string $tla = 'pesek';

    // velja za konstrukcije v stiku z zemljino
    public float $obseg = 0;
    public float $debelinaStene = 0;
    public float $obodniPsi = 0;
    public float $vertPsi = 0;

    private float $Lpi = 0;
    private float $Lpe = 0;

    private \stdClass $dodatnaIzolacija;

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
        $this->protiZraku = $this->konstrukcija->TSG->tip == 'zunanja';
        $this->tla = $config->tla ?? 'pesek';

        $this->obseg = $config->obseg ?? 0;
        $this->debelinaStene = $config->debelinaStene ?? 0;
        $this->obodniPsi = $config->obodniPsi ?? 0;
        $this->vertPsi = $config->vertPsi ?? 0;

        if (!empty($config->dodatnaIzolacija)) {
            $this->dodatnaIzolacija = $config->dodatnaIzolacija;
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
        // temperaturni korekcijski faktor
        $this->b = empty($this->konstrukcija->ogrRazvodT) ? 1 :
            ($this->konstrukcija->ogrRazvodT - $okolje->projektnaZunanjaT) /
            ($cona->notranjaTOgrevanje - $okolje->projektnaZunanjaT);

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

        // napolni podatke o vplivu zemljine
        if ($this->konstrukcija->TSG->tip != 'zunanja') {
            $this->vplivZemljine();
        } else {
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

                $alphaSr = 0.3;
                $Fsky = $this->naklon < 45 ? 1 : 0.5;
                $hri = 4.14;
                $dTsky = 11;

                // sevanje elementa proti nebu za trenutni mesec
                $Qsol = $alphaSr * $this->konstrukcija->Rse * ($this->U + $cona->deltaPsi) * $this->povrsina *
                    $this->faktorSencenja[$mesec] * $this->soncnoObsevanje[$mesec] * $stDni;
                $Qsky = 0.001 * $Fsky * $this->konstrukcija->Rse * ($this->U + $cona->deltaPsi) *
                    $this->povrsina * $hri * $dTsky * $stDni * 24;

                if (!empty($this->konstrukcija->TSG->dobitekSS)) {
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
            $this->H_ogrevanje = $this->H_ogrevanje /
                $temperature['stMesecevOgrevanja'] / ($temperature['povprecnaTOgrevanja'] -
                $okolje->povprecnaLetnaTemp);

            $this->H_hlajenje = $this->H_hlajenje /
                (12 - $temperature['stMesecevOgrevanja']) / ($temperature['povprecnaTHlajenja'] -
                $okolje->povprecnaLetnaTemp);
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
            $lastnostiTal = Configure::read('lookups.tla.' . $this->tla);
            if (empty($lastnostiTal)) {
                die('Neveljaven tip tal.');
            }
            $lambdaTla = $lastnostiTal['lambda'];

            $B = $this->povrsina / (0.5 * $this->obseg);
            $dt = $this->debelinaStene + $lambdaTla * 1 / $this->konstrukcija->U;

            if ($dt < $B) {
                // neizolirana ali srednje izolirana tla
                $U0 = 2 * $lambdaTla / (Pi() * $B + $dt) * log(Pi() * $B / $dt + 1);
            } else {
                // dobro izolirana tla
                $U0 = $lambdaTla / (0.457 * $B + $dt);
            }

            $d_ = 0;
            if (!empty($this->dodatnaIzolacija)) {
                $Rn = $this->dodatnaIzolacija->debelina / $this->dodatnaIzolacija->lambda;
                $R_ = $Rn - $this->dodatnaIzolacija->debelina / $lambdaTla;
                $d_ = $R_ * $lambdaTla;

                if ($this->dodatnaIzolacija->tip == 'horizontalna') {
                    $this->obodniPsi = -$lambdaTla / Pi() * (
                        log($this->dodatnaIzolacija->dolzina / $dt + 1) -
                        log($this->dodatnaIzolacija->dolzina / ($dt + $d_) + 1)
                    );
                } elseif ($this->dodatnaIzolacija->tip == 'vertikalna') {
                    $this->vertPsi = -$lambdaTla / Pi() * (
                        log(2 * $this->dodatnaIzolacija->dolzina / $dt + 1) -
                        log(2 * $this->dodatnaIzolacija->dolzina / ($dt + $d_) + 1)
                    );
                }
            }

            // za tla z obodno izolacijo
            if (!empty($this->dodatnaIzolacija) && $this->dodatnaIzolacija->tip == 'horizontalna') {
                $this->U = $U0 + 2 * $this->obodniPsi / $B;
            } else {
                $this->U = $U0;
            }

            // periodična debelina konstrukcije sigma C.1
            // ne rabimo računati, imamo lookup v tabeli standarda
            //$sigma = sqrt(3.15 * pow(10, 7) * $lambdaTla / (pi() * $lastnostiTal['ro*c']));
            $sigma = $lastnostiTal['sigma'];

            // variacija notranje temperature
            // ISO 13370, C.3.1
            $this->Lpi = $this->povrsina * $lambdaTla / $dt *
                sqrt(2 / (pow(1 + $sigma / $dt, 2) + 1));

            if (!empty($this->dodatnaIzolacija)) {
                if ($this->dodatnaIzolacija->tip == 'horizontalna') {
                    // horizontalna izolacija po obodu
                    // ISO 13370, C.3.6
                    $this->Lpe = 0.37 * $this->obseg * $lambdaTla * (
                        (1 - exp(-$this->dodatnaIzolacija->dolzina / $sigma)) * log($sigma / ($dt + $d_) + 1)
                        +
                        (exp(-$this->dodatnaIzolacija->dolzina / $sigma) * log($sigma / $dt + 1))
                    );
                } elseif ($this->dodatnaIzolacija->tip == 'vertikalna') {
                    // vertikalna izolacija po vertikali oboda
                    // ISO 13370, C.3.7
                    $this->Lpe = 0.37 * $this->obseg * $lambdaTla * (
                        (1 - exp(-2 * $this->dodatnaIzolacija->dolzina / $sigma)) *
                        log($sigma / ($dt + $d_) + 1) +
                        (exp(-2 * $this->dodatnaIzolacija->dolzina / $sigma) * log($sigma / $dt + 1))
                    );
                }
            } else {
                // neizolirana
                // ??
                $this->Lpe = 0.37 * $this->obseg * $lambdaTla * (
                    (1 - exp(-0 / $sigma)) * log($sigma / ($dt + $d_) + 1)
                    +
                    (exp(-0 / $sigma) * log($sigma / $dt + 1))
                );
            }
        }

        if (in_array($this->konstrukcija->TSG->tip, ['stena-teren', 'tla-neogrevano'])) {
            die('Izračun ni podprt.');
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
                    $povprecnaTOgrevanja += $notranjaT[$mesec];
                }
                $stMesecevOgrevanja++;
            }
        }

        $povprecnaTHlajenja = $povprecnaTHlajenja / (12 - $stMesecevOgrevanja);
        $povprecnaTOgrevanja = $povprecnaTOgrevanja / $stMesecevOgrevanja;
        $povprecnaNotranjaT = ($povprecnaTOgrevanja + $cona->notranjaTHlajenje) / 2;

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
            $this->obseg * ($this->vertPsi ?? 0) * ($notranjaT[$mesec] - $okolje->zunanjaT[$mesec]) -
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

        $elementOvoja->protiZraku = $this->protiZraku;
        $elementOvoja->tla = $this->tla;

        $elementOvoja->obseg = $this->obseg;
        $elementOvoja->debelinaStene = $this->debelinaStene;
        $elementOvoja->obodniPsi = $this->obodniPsi;
        $elementOvoja->vertPsi = $this->vertPsi;

        $elementOvoja->dodatnaIzolacija = $this->dodatnaIzolacija;

        return $elementOvoja;
    }
}
