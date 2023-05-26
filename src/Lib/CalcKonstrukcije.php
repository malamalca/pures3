<?php
declare(strict_types=1);

namespace App\Lib;

use App\Core\Configure;
use App\Core\Log;

class CalcKonstrukcije
{
    /**
     * Izračun konstrukcije
     *
     * @param \stdClass $kons Podatki konstrukcije
     * @param \stdClass $okolje Podatki okolja
     * @return \stdClass
     */
    public static function konstrukcija($kons, $okolje)
    {
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
            if (isset($material->lambda) && isset($material->debelina)) {
                $material->R = $material->debelina / $material->lambda;
            }
            $totalR += $material->R;

            $material->Sd = $material->Sd ?? $material->debelina * $material->difuzijskaUpornost;
            $totalSd += $material->Sd;
        }
        $kons->U = 1 / $totalR;
        $kons->Sd = $totalSd;

        foreach (array_keys(Calc::MESECI) as $mesec) {
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
                    $Rt += $sloj->debelina / $sloj->lambda;
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
                foreach (array_keys(Calc::MESECI) as $mesec) {
                    $toplotniTok = ($okolje->notranjaT[$mesec] - $okolje->zunanjaT[$mesec]) * $kons->U;
                    $sloj->T[$mesec] = $okolje->notranjaT[$mesec] - $sloj->Rn * $toplotniTok;

                    $deltaDejanskegaTlaka = $kons->dejanskiTlakSi[$mesec] - $kons->dejanskiTlakSe[$mesec];

                    $sloj->nasicenTlak[$mesec] = Calc::nasicenTlak($sloj->T[$mesec]);
                    $sloj->dejanskiTlak[$mesec] = $kons->dejanskiTlakSi[$mesec] -
                        $deltaDejanskegaTlaka * $sloj->Sdn / $kons->Sd;
                }
            }
        }

        if (!isset($kons->TSG->kontrolaKond) || $kons->TSG->kontrolaKond !== false) {
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
        foreach (array_keys(Calc::MESECI) as $mesec) {
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
                    if ($i < count($kondRavnineVMesecu) - 1) {
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
                }
            } // sizeof($kondRavnine) > 0
        } // foreach (self::MESECI)

        // pa še količino kondenza po mesecih
        if (count($kondRavnine) > 0) {
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
                    $gm = -1;
                } else {
                    $gm = 0;
                    for ($i = $zacetniMesec; $i < $zacetniMesec + 12; $i++) {
                        $mesec = $i % 12;

                        if ($idRavnine > 0) {
                            $tlakLevo = $kondRavnine[$idRavnine - 1]->nasicenTlak[$mesec];
                            $SdLevo = $kondRavnine[$idRavnine - 1]->Sdn;
                        } else {
                            $tlakLevo = $kons->dejanskiTlakSi[$mesec];
                            $SdLevo = 0;
                        }
                        if (!empty($sloj->gc[$mesec])) {
                            $gm += $sloj->gc[$mesec];

                            // največja količina kondenzata v materialu
                            /** @var \stdClass $sloj->material */
                            $sloj->material->gm = ($sloj->material->gm ?? 0) + $sloj->gc[$mesec];
                        } else {
                            if ($gm > 0) {
                                // izračunam izhlapevanje
                                if ($idRavnine == 0 && $mesec > 3) {
                                    $tlakDesno = $kons->dejanskiTlakSe[$mesec];
                                    $SdDesno = $kons->Sd;
                                    $tlakLevo = $kons->dejanskiTlakSi[$mesec];
                                    $SdLevo = 0;
                                } else {
                                    if ($idRavnine < count($kondRavnine) - 1) {
                                        $tlakDesno = $kondRavnine[$idRavnine + 1]->nasicenTlak[$mesec];
                                        $SdDesno = $kondRavnine[$idRavnine + 1]->Sdn;
                                    } else {
                                        $tlakDesno = $kons->dejanskiTlakSe[$mesec];
                                        $SdDesno = $kons->Sd;
                                    }
                                }

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

                                $gm += $gc;

                                $tlakLevo = $sloj->dejanskiTlak[$mesec];
                                $SdLevo = $sloj->Sdn;

                                $sloj->gc[$mesec] = $gc;
                            }
                        }

                        if (isset($sloj->gc[$mesec])) {
                            /** @var \stdClass $sloj */
                            $sloj->gm[$mesec] = $gm < 0 ? 0 : $gm;
                            $kons->gm[$mesec] = ($kons->gm[$mesec] ?? 0) + ($gm < 0 ? 0 : $gm);
                        }
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
}
