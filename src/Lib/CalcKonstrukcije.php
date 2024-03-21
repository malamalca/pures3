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
            if (!isset($kons->Rsi)) {
                $kons->Rsi = $kons->TSG->Rsi;
            }
            if (!isset($kons->Rse)) {
                $kons->Rse = $kons->TSG->Rse;
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
}
