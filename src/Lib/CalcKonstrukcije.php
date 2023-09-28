<?php
declare(strict_types=1);

namespace App\Lib;

use App\Core\Configure;
use App\Core\Log;
use App\Lib\SpanIterators\MonthlySpanIterator;
use Iterator;

class CalcKonstrukcije
{
    /**
     * @var array<int|string, \stdClass>
     */
    public static array $library = [];

    /**
     * @var \Iterator $spanIterator;
     */
    public static ?Iterator $spanIterator = null;

    /**
     * Izračun konstrukcije
     *
     * @param \stdClass $kons Podatki konstrukcije
     * @param \stdClass $okolje Podatki okolja
     * @param array $options Dodatne možnosti
     * @return \stdClass
     */
    public static function konstrukcija($kons, $okolje, $options = [])
    {
        if (!isset(self::$spanIterator)) {
            CalcKonstrukcije::$spanIterator = new MonthlySpanIterator();
        }

        // parametri za posamezno konstrukcijo po TSG
        if (isset($kons->vrsta)) {
            $kons->TSG = Configure::read('lookups.konstrukcije.' . $kons->vrsta);
            if (empty($kons->TSG)) {
                Log::warn(sprintf('Vrsta konstrukcije "%s" po TSG ne obstaja.', $kons->vrsta));
            }
        }

        // 8.1.1. TSG stran 58
        $totalR = $kons->Rsi + $kons->Rse;
        $totalSd = 0;
        foreach ($kons->materiali as $material) {
            $material->R = 0;
            if (!empty($material->sifra)) {
                if (isset(self::$library[$material->sifra])) {
                    /** @var \stdClass $libraryMaterial */
                    $libraryMaterial = self::$library[$material->sifra];

                    /* @phpstan-ignore-next-line */
                    $material->opis = $material->opis ?? $libraryMaterial->opis;
                    /* @phpstan-ignore-next-line */
                    $material->lambda = $material->lambda ?? $libraryMaterial->lambda;
                    /* @phpstan-ignore-next-line */
                    $material->gostota = $material->gostota ?? $libraryMaterial->gostota;
                    /* @phpstan-ignore-next-line */
                    $material->difuzijskaUpornost =
                        $material->difuzijskaUpornost ?? $libraryMaterial->difuzijskaUpornost;
                    /* @phpstan-ignore-next-line */
                    $material->specificnaToplota = $material->specificnaToplota ?? $libraryMaterial->specificnaToplota;
                } else {
                    throw new \Exception(sprintf('Kataloški material "%s" ne obstaja', $material->sifra));
                }
            }
            if (isset($material->lambda) && isset($material->debelina)) {
                $material->R = $material->debelina / $material->lambda;
            }
            $totalR += $material->R;

            $material->Sd = $material->Sd ?? $material->debelina * $material->difuzijskaUpornost;
            $totalSd += $material->Sd;
        }
        $kons->U = 1 / $totalR;
        $kons->Sd = $totalSd;

        foreach (self::$spanIterator as $mesec) {
            $toplotniTok = ($okolje->notranjaT[$mesec] - $okolje->zunanjaT[$mesec]) * $kons->U;
            $kons->Tsi[$mesec] = $okolje->notranjaT[$mesec] - $kons->Rsi * $toplotniTok;
            $kons->Tse[$mesec] = $okolje->zunanjaT[$mesec] + $kons->Rse * $toplotniTok;
            $kons->fRsi[$mesec] = ($kons->Tsi[$mesec] - $okolje->zunanjaT[$mesec]) / ($okolje->notranjaT[$mesec] -
                $okolje->zunanjaT[$mesec]);
            $kons->nasicenTlakSi[$mesec] = Calc::nasicenTlak($kons->Tsi[$mesec]);
            $kons->nasicenTlakSe[$mesec] = Calc::nasicenTlak($kons->Tse[$mesec]);
            $kons->dejanskiTlakSi[$mesec] = Calc::nasicenTlak($okolje->notranjaT[$mesec]) *
                $okolje->notranjaVlaga[$mesec] / 100;
            $kons->dejanskiTlakSe[$mesec] = Calc::nasicenTlak($okolje->zunanjaT[$mesec]) *
                $okolje->zunanjaVlaga[$mesec] / 100;
        }

        $Rt = $kons->Rsi;
        $Sdt = 0;
        foreach ($kons->materiali as $material) {
            // določitev računskih slojev - sloj se razdeli, če je lambda < 0.25 W/mK
            $material->racunskiSloji = [];
            $steviloRacunskihSlojev = 1;
            if (!empty($material->lambda) && (($material->debelina ?? 0) / $material->lambda) > 0.25) {
                $steviloRacunskihSlojev = floor(($material->debelina ?? 0) / (0.25 * $material->lambda)) + 1;
            }
            for ($i = 0; $i < $steviloRacunskihSlojev; $i++) {
                $sloj = new \stdClass();
                $sloj->opis = $material->opis . ($steviloRacunskihSlojev == 1 ? '' : '.' . ($i + 1));
                $sloj->debelina = isset($material->debelina) ? $material->debelina / $steviloRacunskihSlojev : 0;
                $sloj->difuzijskaUpornost = $material->difuzijskaUpornost ?? null;

                if (isset($material->lambda)) {
                    $sloj->lambda = $material->lambda;
                    $Rt += ($sloj->debelina ?? 0) / $sloj->lambda;
                }

                $sloj->Rn = $Rt;

                if (isset($material->Sd)) {
                    $sloj->Sd = $material->Sd / $steviloRacunskihSlojev;
                } else {
                    $sloj->Sd = $sloj->debelina * $sloj->difuzijskaUpornost;
                }
                $Sdt += $sloj->Sd;
                $sloj->Sdn = $Sdt;

                $material->racunskiSloji[] = $sloj;
            }
            // konec določitve računskih slojev

            foreach ($material->racunskiSloji as $sloj) {
                foreach (self::$spanIterator as $mesec) {
                    $toplotniTok = ($okolje->notranjaT[$mesec] - $okolje->zunanjaT[$mesec]) * $kons->U;
                    $sloj->T[$mesec] = $okolje->notranjaT[$mesec] - $sloj->Rn * $toplotniTok;

                    $deltaDejanskegaTlaka = $kons->dejanskiTlakSi[$mesec] - $kons->dejanskiTlakSe[$mesec];

                    $sloj->nasicenTlak[$mesec] = Calc::nasicenTlak($sloj->T[$mesec]);
                    $sloj->dejanskiTlak[$mesec] = $kons->dejanskiTlakSi[$mesec] -
                        $deltaDejanskegaTlaka * $sloj->Sdn / $kons->Sd;
                }
            }

            foreach (self::$spanIterator as $mesec) {
                $toplotniTok = ($okolje->notranjaT[$mesec] - $okolje->zunanjaT[$mesec]) * $kons->U;
                $material->T[$mesec] =
                    $okolje->notranjaT[$mesec] - $Rt * $toplotniTok;
            }
        }

        if (
            !isset($kons->TSG->kontrolaKond) ||
            $kons->TSG->kontrolaKond !== false ||
            !empty($options['izracunKondenzacije'])
        ) {
            self::izracunKondenzacije($kons);

            // sestej kolicino vlage po materialu
            foreach ($kons->materiali as $material) {
                foreach ($material->racunskiSloji as $sloj) {
                    if (isset($sloj->material)) {
                        unset($sloj->material);
                    }
                }
            }
        }

        return $kons;
    }

    /**
     * Funkcija izračuna kondenzacijo v konstrukciji
     *
     * @param \stdClass $kons Konstrukcija
     * @return void
     */
    private static function izracunKondenzacije($kons)
    {
        $kondRavnine = [];
        // iščemo kondenzacijo po mesecih
        foreach (self::$spanIterator as $mesec) {
            $kondRavnineVMesecu = [];

            $i = 0;
            while ($i < 10 && ($kondRavnineKorak = self::isciKondRavnine($kons, $mesec))) {
                // popravimo dejanski tlak
                $kondRavnineVMesecu = array_merge($kondRavnineVMesecu, $kondRavnineKorak);
                self::popravekDejanskegaTlaka($kons, $kondRavnineVMesecu, $mesec);
                $i++;
            }

            if (count($kondRavnineVMesecu) > 0) {
                foreach ($kondRavnineVMesecu as $sloj) {
                    if (!in_array($sloj, $kondRavnine)) {
                        $kondRavnine[] = $sloj;
                    }
                }
                // kondenzacijske ravnine so določene, sedaj poiščemo količino vlage v posamezni ravnini
                $tlakLevo = $kons->dejanskiTlakSi[$mesec];
                $SdLevo = 0;
                foreach ($kondRavnineVMesecu as $i => $sloj) {
                    if (!empty($kondRavnineVMesecu[$i + 1])) {
                        $tlakDesno = $kondRavnineVMesecu[$i + 1]->nasicenTlak[$mesec];
                        $SdDesno = $kondRavnineVMesecu[$i + 1]->Sdn;
                    } else {
                        $tlakDesno = $kons->dejanskiTlakSe[$mesec];
                        $SdDesno = $kons->Sd;
                    }

                    $gc = 2 * pow(10, -10) * cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023) * 24 * 60 * 60 * 1000 *
                    (
                        (($tlakLevo - $sloj->nasicenTlak[$mesec]) / ($sloj->Sdn - $SdLevo))
                        -
                        (($sloj->nasicenTlak[$mesec] - $tlakDesno) / ($SdDesno - $sloj->Sdn))
                    );

                    $tlakLevo = $sloj->dejanskiTlak[$mesec];
                    $SdLevo = $sloj->Sdn;

                    $sloj->gc[$mesec] = $gc;
                    $kons->gc[$mesec] = ($kons->gc[$mesec] ?? 0) + $sloj->gc[$mesec];
                }
            } // sizeof($kondRavnine) > 0
        } // foreach (self::MESECI)

        // pa še količino kondenza po mesecih
        if (count($kondRavnine) > 0) {
            // začetni meseci po id ravnine
            $zacetniMeseci = [];
            foreach ($kondRavnine as $idRavnine => $sloj) {
                // poiščemo začetni mesec
                $zacetniMesec = 0; // januar

                // pomikamo se naprej po mesecih, dokler ne pridemo do decembra ali do sloja, kjer je gc=0
                while ($zacetniMesec <= 11 && !empty($sloj->gc[$zacetniMesec])) {
                    $zacetniMesec++;
                }
                // pomikamo se naprej po mesecih, dokler ne pridemo do decembra ali do sloja, kjer je gc>0
                if ($zacetniMesec < 11) {
                    while ($zacetniMesec <= 11 && empty($sloj->gc[$zacetniMesec])) {
                        $zacetniMesec++;
                    }
                }

                if ($zacetniMesec == 11 && !empty($sloj->gc[$zacetniMesec])) {
                    // začetnega meseca nismo našli, to pomeni, da je kondenzacija skozi celotno leto
                    // in se nalaga in nalaga in nalaga....
                    $zacetniMeseci[$idRavnine] = -1;
                } else {
                    $zacetniMeseci[$idRavnine] = $zacetniMesec;
                }
            }

            // preverim, če je en mesec prej res v vseh slojih brez kondenzacije
            $zacetniMesec = min($zacetniMeseci);
            $zacetniMesecBrezKondenzacije = ($zacetniMesec + 11) % 12;

            $jeMesecBrezKondenzacije = true;
            foreach ($kondRavnine as $idRavnine => $sloj) {
                if (!empty($sloj->gc[$zacetniMesecBrezKondenzacije])) {
                    $jeMesecBrezKondenzacije = false;
                }
            }

            if (!$jeMesecBrezKondenzacije) {
                //$sloj->gm[$mesec] = -1;
                $kons->maxGm = -1;
            } else {
                $kons->maxGm = 0;
                for ($i = $zacetniMesec; $i < $zacetniMesec + 12; $i++) {
                    $mesec = $i % 12;
                    $prejsnjiMesec = ($mesec + 11) % 12;

                    $mesecniGm = 0;

                    foreach ($kondRavnine as $idRavnine => $sloj) {
                        if (!empty($sloj->gc[$mesec])) {
                            // smo v mesecu, ko se kondenzat še nalaga

                            /** @var \stdClass $sloj */
                            $sloj->gm[$mesec] = ($sloj->gm[$prejsnjiMesec] ?? 0) + $sloj->gc[$mesec];

                            // največja količina kondenzata v materialu
                            /** @var \stdClass $sloj->material */
                            $sloj->material->gm[$mesec] = ($sloj->material->gm[$mesec] ?? 0) + $sloj->gc[$mesec];

                            $kons->gm[$mesec] = ($kons->gm[$mesec] ?? 0) + $sloj->gm[$mesec];
                        } else {
                            if (!empty($sloj->gm[$prejsnjiMesec])) {
                                if ($idRavnine > 0) {
                                    $tlakLevo = $kondRavnine[$idRavnine - 1]->nasicenTlak[$mesec];
                                    $SdLevo = $kondRavnine[$idRavnine - 1]->Sdn;
                                } else {
                                    $tlakLevo = $kons->dejanskiTlakSi[$mesec];
                                    $SdLevo = 0;
                                }

                                if (isset($kondRavnine[$idRavnine + 1])) {
                                    $tlakDesno = $kondRavnine[$idRavnine + 1]->nasicenTlak[$mesec];
                                    $SdDesno = $kondRavnine[$idRavnine + 1]->Sdn;
                                } else {
                                    $tlakDesno = $kons->dejanskiTlakSe[$mesec];
                                    $SdDesno = $kons->Sd;
                                }

                                // izhlapevanje
                                /** @var \stdClass $sloj */
                                $gc = 2 * pow(10, -10) * cal_days_in_month(CAL_GREGORIAN, $i % 12 + 1, 2023)
                                    * 24 * 3600 * 1000 *
                                    (
                                        (($tlakLevo - $sloj->nasicenTlak[$mesec]) / ($sloj->Sdn - $SdLevo))
                                        -
                                        (($sloj->nasicenTlak[$mesec] - $tlakDesno) / ($SdDesno - $sloj->Sdn))
                                    );

                                if (empty($sloj->gc[$mesec]) && $gc > 0) {
                                    $gc = 0;
                                }

                                $sloj->gc[$mesec] = $gc;
                                $sloj->gm[$mesec] = ($sloj->gm[$prejsnjiMesec] ?? 0) + $gc;
                                if ($sloj->gm[$mesec] < 0) {
                                    $sloj->gm[$mesec] = 0;
                                    unset($kondRavnine[$idRavnine]);
                                }
                                /** @var \stdClass $sloj->material */
                                $sloj->material->gm[$mesec] = ($sloj->material->gm[$mesec] ?? 0) + $sloj->gc[$mesec];

                                $kons->gc[$mesec] = ($kons->gc[$mesec] ?? 0) + $sloj->gc[$mesec];
                                $kons->gm[$mesec] = ($kons->gm[$mesec] ?? 0) + $sloj->gm[$mesec];
                            }
                        }

                        $mesecniGm += $sloj->gm[$mesec] ?? 0;
                    }

                    if ($kons->maxGm < $mesecniGm) {
                        $kons->maxGm = $mesecniGm;
                    }
                }
            }
        }
    }

    /**
     * Funkcija išče kondenzacijske ravnine v konstrukciji
     *
     * @param \stdClass $kons Podatki konstrukcije
     * @param int $mesec Številka meseca
     * @return array<int, mixed>|null
     */
    public static function isciKondRavnine($kons, $mesec)
    {
        // 0-ni kondenzacije, 1-iscemo največjo razliko, 2-zmanjsevanje
        $kondIskanje = 0;

        /** @var array<int, mixed> $kondRavnine */
        $kondRavnine = [];
        $zadnjaKondenzacija = null;

        foreach ($kons->materiali as $material) {
            foreach ($material->racunskiSloji as $sloj) {
                $deltaTlaka = $sloj->nasicenTlak[$mesec] - $sloj->dejanskiTlak[$mesec];
                if ($deltaTlaka < 0) {
                    if ($kondIskanje == 0) {
                        $kondIskanje = 1;
                        $zadnjaKondenzacija = $sloj;
                    } elseif ($kondIskanje == 1) {
                        $prejsnjaDeltaTlaka = $zadnjaKondenzacija->nasicenTlak[$mesec] -
                            $zadnjaKondenzacija->dejanskiTlak[$mesec];
                        if ($deltaTlaka < $prejsnjaDeltaTlaka) {
                            $zadnjaKondenzacija = $sloj;
                        } else {
                            $zadnjaKondenzacija->material = $material;
                            $kondRavnine[] = $zadnjaKondenzacija;
                            $kondIskanje = 2;
                        }
                    }
                } else {
                    // če smo prišli izven območja kondenzacije
                    if ($kondIskanje == 1) {
                        $zadnjaKondenzacija->material = $material;
                        $kondRavnine[] = $zadnjaKondenzacija;
                        $kondIskanje = 0;
                    } elseif ($kondIskanje == 2) {
                        $kondIskanje = 0;
                    }
                }
            }
        }

        return count($kondRavnine) > 0 ? $kondRavnine : null;
    }

    /**
     * Funkcija popravi potek tlaka vodne pare v konstrukciji tako,
     * da ni linearen ampak da poteka po liniji kondenzacijskih ravnin.
     *
     * @param \stdClass $kons Podatki konstrukcije
     * @param array $kondRavnine Kondenzacijske ravnine
     * @param int $mesec Številka meseca 0..11
     * @return void
     */
    public static function popravekDejanskegaTlaka($kons, $kondRavnine, $mesec)
    {
        $indexRavnine = 0;
        $dejanskiTlakLevo = $kons->dejanskiTlakSi[$mesec];
        $dejanskiTlakDesno = $kondRavnine[0]->nasicenTlak[$mesec];
        $SdLevo = 0;
        $SdDesno = $kondRavnine[0]->Sdn;

        // kondenzacijske ravnine so določene, sedaj prilagodim pdej na da so tangente na kondenzacijske ravnine
        foreach ($kons->materiali as $material) {
            foreach ($material->racunskiSloji as $sloj) {
                // samo za tiste mesece, kjer dejansko poteka kondenzacija ali evaporacija
                //if (!empty($kondRavnine[$indexRavnine]->gc[$mesec])) {
                if ($indexRavnine < count($kondRavnine) && ($sloj == $kondRavnine[$indexRavnine])) {
                    // prišli smo do kondenzacijske ravnine
                    $indexRavnine++;

                    // popravimo dejanski tlak kondenzacijske ravnine na nasičen tlak
                    $sloj->dejanskiTlak[$mesec] = $sloj->nasicenTlak[$mesec];

                    $dejanskiTlakLevo = $sloj->dejanskiTlak[$mesec];
                    $SdLevo = $sloj->Sdn;

                    if ($indexRavnine < count($kondRavnine)) {
                        // naslednja ravnina obstaja, nastavi vrednosti nanjo
                        $dejanskiTlakDesno = $kondRavnine[$indexRavnine]->nasicenTlak[$mesec];
                        $SdDesno = $sloj->Sdn;
                    } else {
                        // ni več kondenzacijskih ravnin
                        $dejanskiTlakDesno = $kons->dejanskiTlakSe[$mesec];
                        $SdDesno = $kons->Sd;
                    }
                } else {
                    $deltaDejanskegaTlaka = $dejanskiTlakLevo - $dejanskiTlakDesno;

                    $sloj->dejanskiTlak[$mesec] = $dejanskiTlakLevo -
                        $deltaDejanskegaTlaka * ($sloj->Sdn - $SdLevo) / ($SdDesno - $SdLevo);
                }
                //}
            }
        }
    }

    /**
     * Preračun za transparentne konstrukcije
     *
     * @param \stdClass $kons Podatki konstrukcije
     * @param \stdClass $okolje Podatki okolja
     * @return \stdClass
     */
    public static function transparentne($kons, $okolje)
    {
        // parametri za posamezno konstrukcijo po TSG
        if (isset($kons->vrsta)) {
            $kons->TSG = Configure::read('lookups.transparentneKonstrukcije.' . $kons->vrsta);
        }

        return $kons;
    }

    /**
     * Graf s podatki o konstrukciji
     *
     * @param array $data Podatki za graf
     * @return string|false
     */
    public static function graf($data)
    {
        if (empty($data['data'])) {
            throw new \Exception('No data!');
        }
        if (empty($data['thickness'])) {
            throw new \Exception('No thickness!');
        }
        if (empty($data['layer'])) {
            throw new \Exception('No layers!');
        }

        if (!empty($data['data2'])) {
            $data2 = $data['data2'];
        }

        // podatki o slojih konstrukcije: naziv | debelina | lambda
        $thicknesses = $data['thickness'];
        $sloji = $data['layer'];
        //$thicknesses = [0.2, 0.2, 0.01];
        //$sloji = ['ab', 'eps', 'fasada'];

        // podatki o temperaturah na posameznem stiku slojev -  T_Si + T_[n] + T_se
        //$data = [20, 19.8, 19, -11, -12.8, -13];
        $data = $data['data'];

        // Image dimensions
        $imageWidth = 600;
        $imageHeight = 400;

        // Grid dimensions and placement within image
        $gridTop = 10;
        $gridLeft = 50;
        $gridBottom = $imageHeight - 40;
        $gridRight = $imageWidth - 50;
        $gridHeight = $gridBottom - $gridTop;
        $gridWidth = $gridRight - $gridLeft;

        // Bar and line width
        $lineWidth = 1;
        $data1lineWidth = 4;
        $data2lineWidth = 2;
        $barWidth = 20;

        // Font settings
        $font = RESOURCES . 'OpenSans-Regular.ttf';
        $fontSize = 8;

        // Margin between label and axis
        $labelMargin = 8;

        // Margin between axis and graph
        $offsetMargin = 20;

        // Max value on y-axis
        $yMaxValue = max($data);
        $yMinValue = min($data);
        if (!empty($data2)) {
            $yMaxValue2 = max($data2);
            $yMinValue2 = min($data2);
            $yMaxValue = max($yMaxValue, $yMaxValue2);
            $yMinValue = min($yMinValue, $yMinValue2);
        }

        $yMaxAxis = $yMaxValue + abs(0.05 * ($yMaxValue - $yMinValue));
        $yMinAxis = $yMinValue - abs(0.05 * ($yMaxValue - $yMinValue));

        // calculate chart lines on y-axis
        //var_dump(($yMaxValue - $yMinValue) / 5);

        // Distance between grid lines on y-axis
        //$yGridStep = 10;
        // Number of lines we want in a grid
        $yGridLinesCount = 4;
        $gridSteps = [1, 2, 5];
        $yGridStepReal = ($yMaxValue - $yMinValue) / $yGridLinesCount;

        $yGridFactor = 1;
        while ($yGridStepReal < 10) {
            $yGridStepReal *= 10;
            $yGridFactor *= 0.1;
        }
        while ($yGridStepReal > 100) {
            $yGridStepReal /= 10;
            $yGridFactor *= 10;
        }

        $yGridStepReal = ((int)$yGridStepReal) / 10;
        $yGridStep = null;
        $yGridMinDifference = 10;
        foreach ($gridSteps as $gridStep) {
            if (abs($gridStep - $yGridStepReal) < $yGridMinDifference) {
                $yGridMinDifference = abs($gridStep - $yGridStepReal);
                $yGridStep = $gridStep;
            }
        }

        $yGridStep = $yGridStep * $yGridFactor * 10;

        $yGridLines = [];
        $yGridStepDiff = ($yMaxValue - $yMinValue) / $yGridLinesCount;

        $yGridLines[] = floor($yMinAxis / $yGridStep) * $yGridStep;
        $yGridLinesCount = floor(($yMaxAxis - $yGridLines[0]) / $yGridStep);

        for ($i = 0; $i < $yGridLinesCount; $i++) {
            $yGridLines[] = $yGridLines[count($yGridLines) - 1] + $yGridStep;
        }

        // Init image
        $chart = imagecreatetruecolor($imageWidth, $imageHeight);
        if (!$chart) {
            return false;
        }

        // Setup colors
        $backgroundColor = imagecolorallocate($chart, 255, 255, 255);
        $axisColor = imagecolorallocate($chart, 85, 85, 85);
        $labelColor = $axisColor;
        $gridColor = imagecolorallocate($chart, 212, 212, 212);
        $barColor = imagecolorallocatealpha($chart, 127, 201, 255, 50);
        $separatorLineColor = imagecolorallocate($chart, 80, 80, 80);
        $dataLineColor = imagecolorallocate($chart, 255, 0, 0);
        $data2LineColor = imagecolorallocate($chart, 64, 64, 255);

        if (
            $backgroundColor === false ||
            $axisColor === false ||
            $labelColor === false ||
            $gridColor === false ||
            $barColor === false ||
            $separatorLineColor === false ||
            $dataLineColor == false ||
            $data2LineColor == false
        ) {
            return false;
        }

        imagefill($chart, 0, 0, $backgroundColor);
        imagesetthickness($chart, $lineWidth);

        /*
        * Print grid lines bottom up
        */
        //for ($i = 0; $i <= $yMaxValue; $i += $yLabelSpan) {
        foreach ($yGridLines as $yGridLineValue) {
            //$y = $gridBottom - $i * $gridHeight / $yMaxAxis;

            $y = $gridBottom - ($yGridLineValue - $yMinAxis) / ($yMaxAxis - $yMinAxis) * $gridHeight;

            // draw the line
            imageline($chart, $gridLeft, (int)$y, $gridRight, (int)$y, $gridColor);

            // draw right aligned label
            $labelBox = imagettfbbox($fontSize, 0, $font, strval($yGridLineValue));
            if ($labelBox) {
                $labelWidth = $labelBox[4] - $labelBox[0];

                $labelX = $gridLeft - $labelWidth - $labelMargin;
                $labelY = $y + $fontSize / 2;

                imagettftext(
                    $chart,
                    $fontSize,
                    0,
                    (int)$labelX,
                    (int)$labelY,
                    $labelColor,
                    $font,
                    strval($yGridLineValue)
                );
            }
        }

        /*
        * Draw x- and y-axis
        */
        imageline($chart, $gridLeft, $gridTop, $gridLeft, $gridBottom, $axisColor);
        imageline($chart, $gridLeft, $gridBottom, $gridRight, $gridBottom, $axisColor);

        /*
        * Draw the bars with labels
        */
        $debelinaKonstrukcije = array_sum($thicknesses);

        $sirinaGrafa = $gridWidth - 2 * $offsetMargin;

        $offsetX = $gridLeft + $offsetMargin;

        $x1 = 0;
        $y1 = 0;
        $x2 = 0;
        $y2 = 0;
        foreach ($sloji as $ix => $sloj) {
            $nazivSloja = $sloj;
            $debelinaSloja = $thicknesses[$ix];

            $x1 = $offsetX;
            $y1 = $gridBottom - $gridHeight;
            $x2 = $offsetX + $debelinaSloja / $debelinaKonstrukcije * $sirinaGrafa;
            $y2 = $gridBottom - 1;

            imagefilledrectangle($chart, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $barColor);

            /* Linija med sloji */
            imageline($chart, (int)$x1, (int)$y1, (int)$x1, (int)$y2, $separatorLineColor);

            // Draw the label
            $labelBox = imagettfbbox($fontSize, 0, $font, $nazivSloja);
            if ($labelBox) {
                $labelWidth = $labelBox[4] - $labelBox[0];

                $labelX = ($x1 + $x2) / 2 - $labelWidth / 2;
                $labelY = $gridBottom + $labelMargin + $fontSize;

                imagettftext($chart, $fontSize, 0, (int)$labelX, $labelY, $labelColor, $font, $nazivSloja);
            }

            $offsetX += $debelinaSloja / $debelinaKonstrukcije * $sirinaGrafa;
        }

        /* Linija med sloji (zadnja) */
        imageline($chart, (int)$x2, (int)$y1, (int)$x2, (int)$y2, $separatorLineColor);

        /** Draw the data (temperature) axis line */

        $dataX = [];

        /** First data point for external temperature */
        $offsetX = $gridLeft + $offsetMargin;

        $dataX[] = $offsetX - $offsetMargin / 2;
        $dataX[] = $offsetX;
        foreach ($thicknesses as $debelinaSloja) {
            $dataX[] = $offsetX + $debelinaSloja / $debelinaKonstrukcije * $sirinaGrafa;
            $offsetX += $debelinaSloja / $debelinaKonstrukcije * $sirinaGrafa;
        }
        $dataX[] = $offsetX + $offsetMargin / 2;

        $prevValue = 0;
        foreach ($dataX as $k => $value) {
            if ($k > 0) {
                $x1 = $prevValue;
                $y1 = $gridBottom - ($data[$k - 1] - $yMinAxis) / ($yMaxAxis - $yMinAxis) * $gridHeight;
                $x2 = $value;
                $y2 = $gridBottom - ($data[$k] - $yMinAxis) / ($yMaxAxis - $yMinAxis) * $gridHeight;

                imagesetthickness($chart, $data1lineWidth);
                imageline($chart, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $dataLineColor);

                if (!empty($data2)) {
                    $x1 = $prevValue;
                    $y1 = $gridBottom - ($data2[$k - 1] - $yMinAxis) / ($yMaxAxis - $yMinAxis) * $gridHeight;
                    $x2 = $value;
                    $y2 = $gridBottom - ($data2[$k] - $yMinAxis) / ($yMaxAxis - $yMinAxis) * $gridHeight;

                    imagesetthickness($chart, $data2lineWidth);
                    imageline($chart, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $data2LineColor);
                }
            }
            $prevValue = $value;
        }

        ob_start();
        imagepng($chart);
        $imageData = ob_get_contents();
        ob_end_clean();

        imagedestroy($chart);

        return $imageData;
    }
}
