<?php
declare(strict_types=1);

namespace App\Lib;

use App\Core\Configure;
use App\Core\Log;

class CalcOvojNetransparenten
{
    /**
     * Glavna funkcija za analizo cone
     *
     * @param \StdClass $cona Podatki o coni
     * @param \StdClass $okolje Podatki o okolju
     * @param array $konstrukcije Seznam konstrukcij
     * @return void
     */
    public static function analiza($cona, $okolje, $konstrukcije)
    {
        $konstrukcije = array_combine(array_map(fn($k) => $k->id, $konstrukcije), $konstrukcije);

        if (isset($cona->ovoj->netransparentneKonstrukcije)) {
            foreach ($cona->ovoj->netransparentneKonstrukcije as $elementOvoja) {
                if (!isset($konstrukcije[$elementOvoja->idKonstrukcije])) {
                    throw \Expection(sprintf('Konstrukcija "" ne obstaja', $elementOvoja->idKonstrukcije));
                }
                $kons = $konstrukcije[$elementOvoja->idKonstrukcije];

                $elementOvoja->stevilo = $element->stevilo ?? 1;
                $elementOvoja->protiZraku = $kons->TSG->tip == 'zunanja';

                $elementOvoja->orientacija = $elementOvoja->orientacija ?? '';
                $elementOvoja->naklon = $elementOvoja->naklon ?? 0;
                $elementOvoja->povrsina = $elementOvoja->povrsina ?? 0;
                $elementOvoja->U = $kons->U;

                // temperaturni korekcijski faktor
                $elementOvoja->b = empty($kons->ogrRazvodT) ? 1 :
                    ($kons->ogrRazvodT - $cona->zunanjaT) / ($cona->notranjaTOgrevanje - $cona->zunanjaT);

                $elementOvoja->faktorSencenja = $elementOvoja->faktorSencenja ?? array_map(fn($m) => 1, Calc::MESECI);

                // faktor sončnega sevanja
                foreach ($okolje->obsevanje as $line) {
                    if ($line->orientacija == $elementOvoja->orientacija && $line->naklon == $elementOvoja->naklon) {
                        $elementOvoja->soncnoObsevanje = $line->obsevanje;
                        break;
                    }
                }
                if (empty($elementOvoja->soncnoObsevanje)) {
                    Log::warn('Sončno obsevanje za element ne obstaja', ['id' => $element->id]);
                }

                // napolni podatke o vplivu zemljine
                if ($kons->TSG->tip != 'zunanja') {
                    self::vplivZemljine($elementOvoja, $kons);
                }

                // izračun dodatnih temperatura
                $temperature = self::osnovniTemperaturniPodatki($elementOvoja, $kons, $cona, $okolje);

                // transmisijske toplotne izgube za ogrevanje in hlajenje Qtr,m (kWh/m)
                foreach (array_keys(Calc::MESECI) as $mesec) {
                    $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
                    if ($kons->TSG->tip == 'zunanja') {
                        // konstrukcija proti zraku
                        $elementOvoja->transIzgubeOgrevanje[$mesec] = ($elementOvoja->U + $cona->deltaPsi) *
                            $elementOvoja->povrsina * $elementOvoja->b * 24 / 1000 *
                            $cona->deltaTOgrevanje[$mesec] * $stDni;

                        $elementOvoja->transIzgubeHlajenje[$mesec] = ($elementOvoja->U + $cona->deltaPsi) *
                            $elementOvoja->povrsina * $elementOvoja->b * 24 / 1000 *
                            $cona->deltaTHlajenje[$mesec] * $stDni;

                        $alphaSr = 0.3;
                        $Fsky = $elementOvoja->naklon < 45 ? 1 : 0.5;
                        $hri = 4.14;
                        $dTsky = 11;

                        // sevanje elementa proti nebu za trenutni mesec
                        $Qsol = $alphaSr * $kons->Rse * ($elementOvoja->U + $cona->deltaPsi) * $elementOvoja->povrsina *
                            $elementOvoja->faktorSencenja[$mesec] * $elementOvoja->soncnoObsevanje[$mesec] * $stDni;
                        $Qsky = 0.001 * $Fsky * $kons->Rse * ($elementOvoja->U + $cona->deltaPsi) *
                            $elementOvoja->povrsina * $hri * $dTsky * $stDni * 24;

                        if (!empty($kons->TSG->dobitekSS)) {
                            $elementOvoja->solarniDobitkiOgrevanje[$mesec] = ($Qsol - $Qsky) / 1000;
                            $elementOvoja->solarniDobitkiHlajenje[$mesec] = ($Qsol - $Qsky) / 1000;
                        } else {
                            $elementOvoja->solarniDobitkiOgrevanje[$mesec] = 0;
                            $elementOvoja->solarniDobitkiHlajenje[$mesec] = 0;
                        }
                    } else {
                        // konstrukcija proti zemljini
                        // TODO: stene, ostale horizontale

                        // toplotni tok proti tlom
                        self::izracunTokaProtiZemljini($mesec, $elementOvoja, $okolje, $temperature);

                        $elementOvoja->solarniDobitkiOgrevanje[$mesec] = 0;
                        $elementOvoja->solarniDobitkiHlajenje[$mesec] = 0;
                    }

                    $cona->transIzgubeOgrevanje[$mesec] +=
                        $elementOvoja->transIzgubeOgrevanje[$mesec] * $elementOvoja->stevilo;
                    $cona->transIzgubeHlajenje[$mesec] +=
                        $elementOvoja->transIzgubeHlajenje[$mesec] * $elementOvoja->stevilo;

                    $cona->solarniDobitkiOgrevanje[$mesec] +=
                        $elementOvoja->solarniDobitkiOgrevanje[$mesec] * $elementOvoja->stevilo;
                    $cona->solarniDobitkiHlajenje[$mesec] +=
                        $elementOvoja->solarniDobitkiHlajenje[$mesec] * $elementOvoja->stevilo;
                }

                if ($kons->TSG->tip != 'zunanja') {
                    $elementOvoja->Hgr_ogrevanje = $elementOvoja->Hgr_ogrevanje /
                        ($temperature['stMesecevOgrevanja'] * $temperature['povprecnaTOgrevanja'] -
                        $okolje->povprecnaLetnaTemp) * $elementOvoja->stevilo;
                    $elementOvoja->Hgr_hlajenje = $elementOvoja->Hgr_hlajenje /
                        ((12 - $temperature['stMesecevOgrevanja']) * $temperature['povprecnaTHlajenja'] -
                        $okolje->povprecnaLetnaTemp) * $elementOvoja->stevilo;

                    // toplotni tok proti zemljini
                    $cona->Hgr_ogrevanje = ($cona->Hgr_ogrevanje ?? 0) + $elementOvoja->Hgr_ogrevanje;
                    $cona->Hgr_hlajenje = ($cona->Hgr_hlajenje ?? 0) + $elementOvoja->Hgr_hlajenje;
                }
            }
        }
    }

    /**
     * Doda podatke o zemljini na konstrukcijo
     *
     * @param \StdClass $elementOvoja Element ovoja
     * @param \StdClass $kons Konstrukcij
     * @return void
     */
    public static function vplivZemljine($elementOvoja, $kons)
    {
        // proti terenu
        if ($kons->TSG->tip == 'tla-teren') {
            $lastnostiTal = Configure::read('lookups.tla.' . $elementOvoja->tla);
            if (empty($lastnostiTal)) {
                die('Neveljaven tip tal.');
            }
            $lambdaTla = $lastnostiTal['lambda'];

            $B = $elementOvoja->povrsina / (0.5 * $elementOvoja->obseg);
            $dt = $elementOvoja->debelinaStene + $lambdaTla * 1 / $kons->U;

            if ($dt < $B) {
                // neizolirana ali srednje izolirana tla
                $U0 = 2 * $lambdaTla / (Pi() * $B + $dt) * log(Pi() * $B / $dt + 1);
            } else {
                // dobro izolirana tla
                $U0 = $lambdaTla / (0.457 * $B + $dt);
            }

            if (!empty($elementOvoja->dodatnaIzolacija)) {
                $Rn = $elementOvoja->dodatnaIzolacija->debelina / $elementOvoja->dodatnaIzolacija->lambda;
                $R_ = $Rn - $elementOvoja->dodatnaIzolacija->debelina / $lambdaTla;
                $d_ = $R_ * $lambdaTla;

                if ($elementOvoja->dodatnaIzolacija->tip == 'horizontalna') {
                    $elementOvoja->obodniPsi = -$lambdaTla / Pi() * (
                        log($elementOvoja->dodatnaIzolacija->dolzina / $dt + 1) -
                        log($elementOvoja->dodatnaIzolacija->dolzina / ($dt + $d_) + 1)
                    );
                } elseif ($elementOvoja->dodatnaIzolacija->tip == 'vertikalna') {
                    $elementOvoja->vertPsi = -$lambdaTla / Pi() * (
                        log(2 * $elementOvoja->dodatnaIzolacija->dolzina / $dt + 1) -
                        log(2 * $elementOvoja->dodatnaIzolacija->dolzina / ($dt + $d_) + 1)
                    );
                }
            }

            // za tla z obodno izolacijo
            if ($elementOvoja->dodatnaIzolacija->tip == 'horizontalna') {
                $elementOvoja->U = $U0 + 2 * $elementOvoja->obodniPsi / $B;
            } else {
                $elementOvoja->U = $U0;
            }

            // periodična debelina konstrukcije sigma C.1
            // ne rabimo računati, imamo lookup v tabeli standarda
            //$sigma = sqrt(3.15 * pow(10, 7) * $lambdaTla / (pi() * $lastnostiTal['ro*c']));
            $sigma = $lastnostiTal['sigma'];

            // variacija notranje temperature
            // ISO 13370, C.3.1
            $elementOvoja->Lpi = $elementOvoja->povrsina * $lambdaTla / $dt *
                sqrt(2 / (pow(1 + $sigma / $dt, 2) + 1));

            if ($elementOvoja->dodatnaIzolacija->tip == 'horizontalna') {
                // horizontalna izolacija po obodu
                // ISO 13370, C.3.6
                $elementOvoja->Lpe = 0.37 * $elementOvoja->obseg * $lambdaTla * (
                    (1 - exp(-$elementOvoja->dodatnaIzolacija->dolzina / $sigma)) * log($sigma / ($dt + $d_) + 1)
                    +
                    (exp(-$elementOvoja->dodatnaIzolacija->dolzina / $sigma) * log($sigma / $dt + 1))
                );
            } elseif ($elementOvoja->dodatnaIzolacija->tip == 'vertikalna') {
                // vertikalna izolacija po vertikali oboda
                // ISO 13370, C.3.7
                $elementOvoja->Lpe = 0.37 * $elementOvoja->obseg * $lambdaTla * (
                    (1 - exp(-2 * $elementOvoja->dodatnaIzolacija->dolzina / $sigma)) * log($sigma / ($dt + $d_) + 1)
                    +
                    (exp(-2 * $elementOvoja->dodatnaIzolacija->dolzina / $sigma) * log($sigma / $dt + 1))
                );
            } else {
                // neizolirana
                // ??
                $elementOvoja->Lpe = 0.37 * $elementOvoja->obseg * $lambdaTla * (
                    (1 - exp(-0 / $sigma)) * log($sigma / ($dt + $d_) + 1)
                    +
                    (exp(-0 / $sigma) * log($sigma / $dt + 1))
                );
            }
        }

        if (in_array($kons->TSG->tip, ['stena-teren', 'tla-neogrevano'])) {
            die('Izračun ni podprt.');
        }
    }

    /**
     * Izračun dodatnih temperaturnih podatkov
     *
     * @param \StdClass $elementOvoja Element ovoja
     * @param \StdClass $kons Podatki konstrukcije
     * @param \StdClass $cona Podatki cone
     * @param \StdClass $okolje Podatki okolja
     * @return array
     */
    public static function osnovniTemperaturniPodatki($elementOvoja, $kons, $cona, $okolje)
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
                if (empty($kons->TSG->talnoGretje)) {
                    $notranjaT[$mesec] = $cona->notranjaTOgrevanje;
                } else {
                    $notranjaT[$mesec] = $okolje->zunanjaT[$mesec] +
                        $elementOvoja->b * ($cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec]);
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
     * @param \StdClass $elementOvoja Podatki elementa ovoja
     * @param \StdClass $okolje Podatki okolja
     * @param array $temperature Dodatne temperature iz osnovniTemperaturniPodatki()
     * @return void
     */
    public static function izracunTokaProtiZemljini($mesec, $elementOvoja, $okolje, $temperature)
    {
        extract($temperature);
        $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

        $urniToplotniTok = $elementOvoja->U *
            $elementOvoja->povrsina * ($povprecnaNotranjaT - $okolje->povprecnaLetnaTemp) +
            $elementOvoja->obseg * ($elementOvoja->vertPsi ?? 0) * ($notranjaT[$mesec] - $okolje->zunanjaT[$mesec]) -
            $elementOvoja->Lpi * ($povprecnaNotranjaT - $notranjaT[$mesec]) +
            $elementOvoja->Lpe * ($okolje->povprecnaLetnaTemp - $okolje->zunanjaT[$mesec]);

        $elementOvoja->transIzgubeOgrevanje[$mesec] = $urniToplotniTok * 24 * $stDni / 1000;
        $elementOvoja->transIzgubeHlajenje[$mesec] = $elementOvoja->transIzgubeOgrevanje[$mesec];

        if (Calc::jeMesecBrezOgrevanja($mesec)) {
            $elementOvoja->Hgr_hlajenje = ($elementOvoja->Hgr_hlajenje ?? 0) + $urniToplotniTok;
        } else {
            $elementOvoja->Hgr_ogrevanje = ($elementOvoja->Hgr_ogrevanje ?? 0) + $urniToplotniTok;
        }
    }
}
