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
     * @param array<string, mixed> $options Dodatne možnosti
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
                throw new \Exception(sprintf('Vrsta konstrukcije "%s" po TSG ne obstaja.', $kons->vrsta));
            }
        }

        if (!empty($options['referencnaStavba'])) {
            $referencnaKons = Configure::read('lookups.referencneKonstrukcije.' . $kons->TSG->idReferencneKonstrukcije);
            if (empty($referencnaKons)) {
                Log::error(sprintf('Referenčna konstrukcija "%s" ne obstaja.', $kons->vrsta));
            }
            $kons->materiali = $referencnaKons->materiali;
        }

        // 8.1.1. TSG stran 58
        $totalR = $kons->Rsi + $kons->Rse;
        $totalSd = 0;
        $debelina = 0;
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

            $debelina += ($material->debelina ?? 0);

            $material->Sd = $material->Sd ?? $material->debelina * ($material->difuzijskaUpornost ?? 0);
            $totalSd += $material->Sd;
        }
        $kons->U = 1 / $totalR;
        $kons->Sd = $totalSd;
        $kons->debelina = $debelina;

        if (!empty($options['referencnaStavba'])) {
            // todo: takole dele Excel
            //$kons->U = $kons->TSG->Umax;
        }

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

                $material->racunskiSloji[] = $sloj;
            }
            // konec določitve računskih slojev

            foreach ($material->racunskiSloji as $sloj) {
                foreach (self::$spanIterator as $mesec) {
                    $toplotniTok = ($okolje->notranjaT[$mesec] - $okolje->zunanjaT[$mesec]) * $kons->U;
                    $sloj->T[$mesec] = $okolje->notranjaT[$mesec] - $sloj->Rn * $toplotniTok;
                }
            }

            foreach (self::$spanIterator as $mesec) {
                $toplotniTok = ($okolje->notranjaT[$mesec] - $okolje->zunanjaT[$mesec]) * $kons->U;
                $material->T[$mesec] = $okolje->notranjaT[$mesec] - $Rt * $toplotniTok;
            }
        }

        $izracunKondentacije = !isset($kons->TSG->kontrolaKond) || $kons->TSG->kontrolaKond !== false;
        $izracunKondentacije = !isset($options['referencnaStavba']) || !$options['referencnaStavba'];
        $izracunKondentacije = $izracunKondentacije || !empty($options['izracunKondenzacije']);

        if ($izracunKondentacije) {
            $Sdt = 0;
            foreach ($kons->materiali as $material) {
                foreach ($material->racunskiSloji as $sloj) {
                    if (isset($material->Sd)) {
                        $sloj->Sd = $material->Sd / count($material->racunskiSloji);
                    } else {
                        $sloj->Sd = $sloj->debelina * $sloj->difuzijskaUpornost;
                    }
                    $Sdt += $sloj->Sd;
                    $sloj->Sdn = $Sdt;

                    foreach (self::$spanIterator as $mesec) {
                        $deltaDejanskegaTlaka = $kons->dejanskiTlakSi[$mesec] - $kons->dejanskiTlakSe[$mesec];

                        $sloj->nasicenTlak[$mesec] = Calc::nasicenTlak($sloj->T[$mesec]);
                        $sloj->dejanskiTlak[$mesec] = $kons->dejanskiTlakSi[$mesec] -
                            $deltaDejanskegaTlaka * $sloj->Sdn / $kons->Sd;
                    }
                }
            }

            $racunskiSloji = [];
            foreach ($kons->materiali as $material) {
                foreach ($material->racunskiSloji as $sloj) {
                    $sloj->material = $material;
                    $racunskiSloji[] = $sloj;
                }
            }

            // naredim popravek tlaka in določim kondenzacijske ravnine
            foreach (self::$spanIterator as $mesec) {
                self::izracunTlakaRekurzija($kons, $racunskiSloji, $mesec, 0, count($racunskiSloji) - 1);
            }

            // izračunam dejansko količino kondenzacije
            self::izracunKondenzacije($kons);

            // odstrani odvečne podatke
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
     * Preračun dejanskega tlaka in določitev kondenzacijskih ravnin.
     * Rutina dela rekurzivno, tako da poišče najnižjo točko nasičenega tlaka v segmentu -
     * najprej vzame za segment celotno širino, v drugi globini ponovi za segmenta levo in desno od kond. ravnine,...
     * Start in end indeksa pri večjih globinah vključujeta predhodno določeno konenzacijsko ravnino.
     *
     * @param \stdClass $kons Podatki konstrukcije
     * @param array<\stdClass> $racunskiSloji Array vseh računskih slojev po vrsti
     * @param int $mesec Številka meseca 0..11
     * @param int $start Startni index segmenta
     * @param int $end Končni index segmenta
     * @param int $depth Globina iteracije
     * @return void
     */
    private static function izracunTlakaRekurzija($kons, $racunskiSloji, $mesec, $start, $end, $depth = 1)
    {
        $maxRazlika = 0;
        $maxIndex = -1;
        for ($i = $start; $i <= $end; $i++) {
            $razlikaDoNasicenegaTlaka =
                $racunskiSloji[$i]->dejanskiTlak[$mesec] - $racunskiSloji[$i]->nasicenTlak[$mesec];
            if ($razlikaDoNasicenegaTlaka > $maxRazlika) {
                $maxRazlika = $razlikaDoNasicenegaTlaka;
                $maxIndex = $i;
            }
        }

        if ($maxIndex >= 0) {
            // konenzacijska ravnina v tem segmentu obstaja na indexu $maxIndex
            // v tem primeru popravim tlake na preostalih konstrukcijah
            $racunskiSloji[$maxIndex]->dejanskiTlak[$mesec] = $racunskiSloji[$maxIndex]->nasicenTlak[$mesec];
            $racunskiSloji[$maxIndex]->kondenzacijskaRavnina[$mesec] = true;

            // popravim tlak slojev levo od ravnine
            if ($start == 0) {
                $dejanskiTlakStart = $kons->dejanskiTlakSi[$mesec];
                $SdStart = 0;
            } else {
                $dejanskiTlakStart = $racunskiSloji[$start]->dejanskiTlak[$mesec];
                $SdStart = $racunskiSloji[$start]->Sdn;
            }
            $dejanskiTlakEnd = $racunskiSloji[$maxIndex]->dejanskiTlak[$mesec];
            $SdEnd = $racunskiSloji[$maxIndex]->Sdn;

            for ($i = $start; $i < $maxIndex; $i++) {
                $racunskiSloji[$i]->dejanskiTlak[$mesec] = $dejanskiTlakStart +
                    ($racunskiSloji[$i]->Sdn - $SdStart) / ($SdEnd - $SdStart) *
                    ($dejanskiTlakEnd - $dejanskiTlakStart);
            }

            self::izracunTlakaRekurzija($kons, $racunskiSloji, $mesec, 0, $maxIndex, $depth + 1);

            // popravim tlak slojev desno od ravnine
            if ($end == count($racunskiSloji) - 1) {
                $dejanskiTlakEnd = $kons->dejanskiTlakSe[$mesec];
                $SdEnd = $kons->Sd;
            } else {
                $dejanskiTlakEnd = $racunskiSloji[$end]->dejanskiTlak[$mesec];
                $SdEnd = $racunskiSloji[$end]->Sdn;
            }
            $dejanskiTlakStart = $racunskiSloji[$maxIndex]->dejanskiTlak[$mesec];
            $SdStart = $racunskiSloji[$maxIndex]->Sdn;

            for ($i = $maxIndex + 1; $i < $end; $i++) {
                $racunskiSloji[$i]->dejanskiTlak[$mesec] = $dejanskiTlakStart +
                    ($SdStart - $racunskiSloji[$i]->Sdn) / ($SdEnd - $SdStart) *
                        ($dejanskiTlakStart - $dejanskiTlakEnd);
            }

            self::izracunTlakaRekurzija($kons, $racunskiSloji, $mesec, $maxIndex, $end, $depth + 1);
        }
    }

    /**
     * Funkcija izračuna kondenzacijo v konstrukciji
     *
     * @param \stdClass $kons Konstrukcija
     * @return void
     */
    private static function izracunKondenzacije($kons)
    {
        // vse kond. ravnine ne glede na mesece
        /** @var array<int, \stdClass> $kondRavnine */
        $kondRavnine = [];
        foreach ($kons->materiali as $material) {
            foreach ($material->racunskiSloji as $sloj) {
                if (isset($sloj->kondenzacijskaRavnina)) {
                    $kondRavnine[] = $sloj;
                }
            }
        }

        // iščemo kondenzacijo po mesecih
        foreach (self::$spanIterator as $mesec) {
            // kond. ravnine v trenutnem mesecu
            /** @var array<int, \stdClass> $kondRavnineVMesecu */
            $kondRavnineVMesecu = [];
            foreach ($kons->materiali as $material) {
                foreach ($material->racunskiSloji as $sloj) {
                    if (isset($sloj->kondenzacijskaRavnina[$mesec])) {
                        $kondRavnineVMesecu[] = $sloj;
                    }
                }
            }

            if (count($kondRavnineVMesecu) > 0) {
                // kondenzacijske ravnine so določene, sedaj poiščemo količino vlage v posamezni ravnini
                $tlakLevo = $kons->dejanskiTlakSi[$mesec];
                $SdLevo = 0;
                foreach ($kondRavnineVMesecu as $i => $sloj) {
                    /** @var \stdClass $sloj */
                    if (!empty($kondRavnineVMesecu[$i + 1])) {
                        /** @var \stdClass $naslednjaKondRavnina */
                        $naslednjaKondRavnina = $kondRavnineVMesecu[$i + 1];
                        $tlakDesno = $naslednjaKondRavnina->nasicenTlak[$mesec];
                        $SdDesno = $naslednjaKondRavnina->Sdn;
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
            } // sizeof($kondRavnineVMesecu) > 0
        } // foreach (self::MESECI)

        // pa še količino kondenza po mesecih
        if (count($kondRavnine) > 0) {
            // začetni meseci po id ravnine
            $zacetniMeseci = [];
            foreach ($kondRavnine as $idRavnine => $sloj) {
                // poiščemo začetni mesec
                $zacetniMesecUp = 0; // januar

                // pomikamo se naprej po mesecih, dokler ne pridemo do decembra ali do sloja, kjer je gc=0
                while ($zacetniMesecUp <= 11 && !empty($sloj->gc[$zacetniMesecUp])) {
                    $zacetniMesecUp++;
                }

                // pomikamo se naprej po mesecih, dokler ne pridemo do decembra ali do sloja, kjer je gc>0
                if ($zacetniMesecUp < 11) {
                    $zacetniMesec = $zacetniMesecUp;
                    while ($zacetniMesec <= 11 && empty($sloj->gc[$zacetniMesec])) {
                        $zacetniMesec++;
                    }
                } else {
                    $zacetniMesec = 11;
                }

                if ($zacetniMesecUp == 11 && !empty($sloj->gc[$zacetniMesec])) {
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
                                if ($idRavnine > 0 && isset($kondRavnine[$idRavnine - 1])) {
                                    /** @var \stdClass $prejsnjaKondRavnina */
                                    $prejsnjaKondRavnina = $kondRavnine[$idRavnine - 1];
                                    $tlakLevo = $prejsnjaKondRavnina->nasicenTlak[$mesec];
                                    $SdLevo = $prejsnjaKondRavnina->Sdn;
                                } else {
                                    $tlakLevo = $kons->dejanskiTlakSi[$mesec];
                                    $SdLevo = 0;
                                }

                                if (isset($kondRavnine[$idRavnine + 1])) {
                                    /** @var \stdClass $naslednjaKondRavnina */
                                    $naslednjaKondRavnina = $kondRavnine[$idRavnine + 1];
                                    $tlakDesno = $naslednjaKondRavnina->nasicenTlak[$mesec];
                                    $SdDesno = $naslednjaKondRavnina->Sdn;
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
     * Preračun za transparentne konstrukcije
     *
     * @param \stdClass $kons Podatki konstrukcije
     * @param \stdClass $okolje Podatki okolja
     * @param array<string, mixed> $options Dodatne možnosti
     * @return \stdClass
     */
    public static function transparentne($kons, $okolje, $options = [])
    {
        // parametri za posamezno konstrukcijo po TSG
        if (isset($kons->vrsta)) {
            $kons->TSG = Configure::read('lookups.transparentneKonstrukcije.' . $kons->vrsta);
            if (empty($kons->TSG)) {
                Log::error(sprintf('TSG konstrukcija "%s" ne obstaja.', $kons->vrsta));
            }
        }

        if (!empty($options['referencnaStavba'])) {
            $referencnaKons = Configure::read('lookups.referencneKonstrukcije.' . $kons->TSG->idReferencneKonstrukcije);
            if (empty($referencnaKons)) {
                Log::error(sprintf('Referenčna konstrukcija "%s" ne obstaja.', $kons->vrsta));
            }

            unset($kons->Ud);
            unset($kons->Uf);
            unset($kons->Ug);
            unset($kons->g);
            unset($kons->Psi);

            if (isset($referencnaKons->lastnosti->Ud)) {
                $kons->Ud = $referencnaKons->lastnosti->Ud;
            }
            if (isset($referencnaKons->lastnosti->Uw)) {
                $kons->Uw = $referencnaKons->lastnosti->Uw;
            }
            if (isset($referencnaKons->lastnosti->g)) {
                $kons->g = $referencnaKons->lastnosti->g;
            }
        }

        return $kons;
    }

    /**
     * Graf s podatki o konstrukciji
     *
     * @param mixed $data Podatki za graf
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
            /** @var array<int,float> $data2 */
            $data2 = $data['data2'];
        }

        $colors = $data['color'] ?? null;

        // podatki o slojih konstrukcije: naziv | debelina | lambda
        /** @var array<int,float> $thicknesses */
        $thicknesses = $data['thickness'];

        /** @var array<int,string> $sloji */
        $sloji = $data['layer'];
        //$thicknesses = [0.2, 0.2, 0.01];
        //$sloji = ['ab', 'eps', 'fasada'];

        // podatki o temperaturah na posameznem stiku slojev -  T_Si + T_[n] + T_se
        //$data = [20, 19.8, 19, -11, -12.8, -13];
        /** @var array<int,float> $data */
        $data = $data['data'];

        // Image dimensions
        $imageWidth = 800;
        $imageHeight = 600;

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

        // Font settings
        $font = RESOURCES . 'OpenSans-Regular.ttf';
        $fontSize = 8;

        // Margin between label and axis
        $labelMargin = 8;

        // Margin between axis and graph
        $offsetMargin = 20;

        // Max value on y-axis
        $yMaxValue = (float)max((array)$data);
        $yMinValue = (float)min((array)$data);
        if (!empty($data2)) {
            $yMaxValue2 = (float)max((array)$data2);
            $yMinValue2 = (float)min((array)$data2);
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

        $colorPalette = [
            '1' => imagecolorallocate($chart, 235, 189, 52),
            '2' => imagecolorallocate($chart, 235, 125, 52),
            '3' => imagecolorallocate($chart, 138, 127, 120),
            '4' => imagecolorallocate($chart, 110, 100, 100),
        ];

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
        $debelinaKonstrukcije = array_sum((array)$thicknesses);

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

            imagefilledrectangle(
                $chart,
                (int)$x1,
                (int)$y1,
                (int)$x2,
                (int)$y2,
                $colors ? (int)$colorPalette[$colors[$ix]] : $barColor
            );

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

    /**
     * Klasičen line-graf
     *
     * @param array<string, mixed> $data Podatki za graf
     * @return string|false
     */
    public static function lineGraph($data)
    {
        // Image dimensions
        $imageWidth = 800;
        $imageHeight = 600;

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

        // Font settings
        $font = RESOURCES . 'OpenSans-Regular.ttf';
        $fontSize = 12;

        // Margin between label and axis
        $labelMargin = 8;

        // Margin between axis and graph
        $offsetMargin = 20;

        // Max value on y-axis
        $maxSeriesValues = [];
        $minSeriesValues = [];

        foreach ($data['series'] as $k => $serie) {
            $maxSeriesValues[$k] = max($serie);
            $minSeriesValues[$k] = min($serie);
        }
        $yMaxValue = max($maxSeriesValues);
        $yMinValue = min($minSeriesValues);

        $yMaxAxis = $yMaxValue + abs(0.05 * ($yMaxValue - $yMinValue));
        $yMinAxis = $yMinValue - abs(0.05 * ($yMaxValue - $yMinValue));

        // Distance between grid lines on y-axis
        // $yGridStep = 10;
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

        // First horizontal grid line
        $firstGridLinePos = floor($yMinAxis / $yGridStep) * $yGridStep;
        if ($firstGridLinePos > $gridBottom) {
            $yGridLines[] = $firstGridLinePos;
        } else {
            $yGridLines[] = $firstGridLinePos + $yGridStep;
        }

        $yGridLinesCount = floor(($yMaxAxis - $firstGridLinePos) / $yGridStep);

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

        /** Draw the data series line */
        $dataX = [];

        $sirinaGrafa = $gridWidth - 2 * $offsetMargin;
        $offsetX = $gridLeft + $offsetMargin;
        foreach ($data['X'] as $dataValue) {
            $dataX[] = $offsetX + $dataValue / max($data['X']) * $sirinaGrafa;
        }

        $prevLabelX = $gridLeft;
        foreach ($dataX as $k => $value) {
            if ($k > 0) {
                // draw series line
                foreach ($data['series'] as $j => $serie) {
                    $x1 = $dataX[$k - 1];
                    $y1 = $gridBottom - ($serie[$k - 1] - $yMinAxis) / ($yMaxAxis - $yMinAxis) * $gridHeight;
                    $x2 = $value;
                    $y2 = $gridBottom - ($serie[$k] - $yMinAxis) / ($yMaxAxis - $yMinAxis) * $gridHeight;

                    imagesetthickness($chart, $data1lineWidth);
                    imageline($chart, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $dataLineColor);
                }

                /* Grid Line */
                $x1 = $value;
                $y1 = $gridBottom - $gridHeight;
                $x2 = $value;
                $y2 = $gridBottom - 1;

                imagefilledrectangle(
                    $chart,
                    (int)$x1,
                    (int)$y1,
                    (int)$x2,
                    (int)$y2,
                    $gridColor
                );

                // draw right aligned label
                $labelBox = imagettfbbox($fontSize, 0, $font, strval(round($data['X'][$k], 0)));
                if ($labelBox) {
                    $labelWidth = $labelBox[4] - $labelBox[0];

                    $labelX = $value - $labelWidth / 2;
                    $labelY = $gridBottom + $fontSize + $labelMargin;

                    // prevent overlap
                    if ($labelX > $prevLabelX) {
                        imagettftext(
                            $chart,
                            $fontSize,
                            0,
                            $labelX,
                            $labelY,
                            $labelColor,
                            $font,
                            strval(round($data['X'][$k], 0))
                        );
                        $prevLabelX = $labelX + $labelWidth;
                    }
                }
            }
        }

        ob_start();
        imagepng($chart);
        $imageData = ob_get_contents();
        ob_end_clean();

        imagedestroy($chart);

        return $imageData;
    }
}
