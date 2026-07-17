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

        // faktor toplotne stabilnosti f (dušilni/dekrementni faktor, SIST EN ISO 13786; TSG t. 8.1.5)
        $kons->f = self::faktorToplotneStabilnosti($kons);

        self::vplivZemljine($kons);

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

        $izracunKondenzacije =
            (!isset($kons->TSG->kontrolaKond) || $kons->TSG->kontrolaKond !== false) &&
            (empty($options['referencnaStavba']));
        $izracunKondenzacije = $izracunKondenzacije || !empty($options['izracunKondenzacije']);
        // brez difuzijskih podatkov (Sd = 0) prehoda vodne pare ni mogoče izračunati
        $izracunKondenzacije = $izracunKondenzacije && $kons->Sd > 0;

        if ($izracunKondenzacije) {
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
     * Faktor toplotne stabilnosti f (dušilni/dekrementni faktor) gradnika po SIST EN ISO 13786,
     * skladno s točko 8.1.5 smernice TSG-1-004. Določen je kot razmerje med periodično toplotno
     * prehodnostjo |Yie| (perioda 24 h) in stacionarno toplotno prehodnostjo U: f = |Yie| / U.
     * Vrne null, kadar dinamičnih lastnosti slojev ni mogoče določiti (manjka gostota/specifična toplota).
     *
     * @param \stdClass $kons Podatki konstrukcije (z izračunanim U, Rsi, Rse in materiali)
     * @return float|null
     */
    public static function faktorToplotneStabilnosti($kons): ?float
    {
        foreach ($kons->materiali as $material) {
            if (
                empty($material->debelina) || empty($material->lambda) ||
                empty($material->gostota) || empty($material->specificnaToplota)
            ) {
                return null;
            }
        }

        $perioda = 86400.0; // 24 h v sekundah

        // toplotna matrika gradnika (kompleksna 2x2), od notranje proti zunanji strani
        $Z = self::kompleksniProdukt(self::kompleksnaMatrikaUpora($kons->Rsi), self::kompleksnaEnotskaMatrika());
        foreach ($kons->materiali as $material) {
            $Z = self::kompleksniProdukt($Z, self::kompleksnaMatrikaSloja(
                (float)$material->debelina,
                (float)$material->lambda,
                (float)$material->gostota,
                (float)$material->specificnaToplota,
                $perioda
            ));
        }
        $Z = self::kompleksniProdukt($Z, self::kompleksnaMatrikaUpora($kons->Rse));

        $absZ12 = sqrt($Z['12'][0] ** 2 + $Z['12'][1] ** 2);
        if ($absZ12 == 0.0) {
            return null;
        }

        // periodična toplotna prehodnost Yie = -1/Z12; |Yie| = 1/|Z12|
        return 1 / $absZ12 / $kons->U;
    }

    /**
     * Enotska kompleksna 2x2 matrika.
     *
     * @return array
     */
    private static function kompleksnaEnotskaMatrika(): array
    {
        return ['11' => [1.0, 0.0], '12' => [0.0, 0.0], '21' => [0.0, 0.0], '22' => [1.0, 0.0]];
    }

    /**
     * Toplotna matrika mejnega (zračnega) sloja z uporom R.
     *
     * @param float $R Toplotni upor (m2K/W)
     * @return array
     */
    private static function kompleksnaMatrikaUpora(float $R): array
    {
        return ['11' => [1.0, 0.0], '12' => [-$R, 0.0], '21' => [0.0, 0.0], '22' => [1.0, 0.0]];
    }

    /**
     * Toplotna matrika homogenega sloja po SIST EN ISO 13786.
     *
     * @param float $d Debelina sloja (m)
     * @param float $lambda Toplotna prevodnost (W/(mK))
     * @param float $ro Gostota (kg/m3)
     * @param float $c Specifična toplota (J/(kgK))
     * @param float $perioda Perioda nihanja (s)
     * @return array
     */
    private static function kompleksnaMatrikaSloja(float $d, float $lambda, float $ro, float $c, float $perioda): array
    {
        $delta = sqrt($lambda * $perioda / (M_PI * $ro * $c)); // globina prodiranja
        $xi = $d / $delta;

        $sh = sinh($xi);
        $ch = cosh($xi);
        $sn = sin($xi);
        $cs = cos($xi);

        $z11 = [$ch * $cs, $sh * $sn];
        $f12 = $delta / (2 * $lambda);
        $z12 = [-$f12 * ($sh * $cs + $ch * $sn), -$f12 * ($ch * $sn - $sh * $cs)];
        $f21 = $lambda / $delta;
        $z21 = [-$f21 * ($sh * $cs - $ch * $sn), -$f21 * ($sh * $cs + $ch * $sn)];

        return ['11' => $z11, '12' => $z12, '21' => $z21, '22' => $z11];
    }

    /**
     * Produkt dveh kompleksnih 2x2 matrik.
     *
     * @param array $A Matrika A
     * @param array $B Matrika B
     * @return array
     */
    private static function kompleksniProdukt(array $A, array $B): array
    {
        $mul = fn(array $x, array $y): array => [$x[0] * $y[0] - $x[1] * $y[1], $x[0] * $y[1] + $x[1] * $y[0]];
        $add = fn(array $x, array $y): array => [$x[0] + $y[0], $x[1] + $y[1]];

        return [
            '11' => $add($mul($A['11'], $B['11']), $mul($A['12'], $B['21'])),
            '12' => $add($mul($A['11'], $B['12']), $mul($A['12'], $B['22'])),
            '21' => $add($mul($A['21'], $B['11']), $mul($A['22'], $B['21'])),
            '22' => $add($mul($A['21'], $B['12']), $mul($A['22'], $B['22'])),
        ];
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

                    $gc = 2 * pow(10, -10) * Calc::steviloDni($mesec) * 24 * 60 * 60 * 1000 *
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
                                $gc = 2 * pow(10, -10) * Calc::steviloDni($i % 12)
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
     * Izračun vpliva zemljine na konstrukcijo
     *
     * @param \stdClass $kons Podatki konstrukcije
     * @return void
     */
    public static function vplivZemljine($kons)
    {
        if (!isset($kons->vplivZemljine)) {
            return;
        }

        $result = self::izracunajVplivZemljine($kons->TSG->tip ?? '', $kons->U, $kons->vplivZemljine);

        if ($result !== null) {
            $kons->U_earth = $result->U_earth;
        }
    }

    /**
     * Izračun vpliva zemljine - osrednja logika
     *
     * @param string $tip TSG tip ('tla-teren', 'stena-teren', 'tla-neogrevano')
     * @param float $U_konstrukcije Materialni U konstrukcije
     * @param \stdClass $params Parametri (tla, povrsina, obseg, debelinaStene, ...)
     * @return \stdClass|null Rezultat z U_earth, obodniPsi, Lpi, Lpe
     */
    public static function izracunajVplivZemljine($tip, $U_konstrukcije, $params)
    {
        if (is_string($params->tla ?? null)) {
            $tlaLambda = ['glina' => 1.5, 'pesek' => 2, 'kamen' => 3.5][$params->tla] ?? 2;
            $tlaSigma = ['glina' => 2.2, 'pesek' => 3.2, 'kamen' => 4.2][$params->tla] ?? 3.2;
        } elseif (isset($params->tla)) {
            $tlaLambda = $params->tla->lambda ?? 2;
            $tlaSigma = $params->tla->sigma ?? 3.2;
        } else {
            $tlaLambda = 2;
            $tlaSigma = 3.2;
        }

        $U_earth = null;
        $obodniPsi = null;
        $Lpi = 0;
        $Lpe = 0;

        if ($tip == 'tla-teren') {
            if (!isset($params->obseg)) {
                throw new \Exception(
                    sprintf('Konstrukcija %s nima definiranega obsega proti zemljini.', $params->id ?? '')
                );
            }

            $B = $params->povrsina / (0.5 * $params->obseg);
            $dt = $params->debelinaStene + $tlaLambda * 1 / $U_konstrukcije;

            if ($dt < $B) {
                $U_earth = 2 * $tlaLambda / (Pi() * $B + $dt + 0.5 * ($params->globina ?? 0)) *
                    log(Pi() * $B / ($dt + 0.5 * ($params->globina ?? 0)) + 1);
            } else {
                $U_earth = $tlaLambda / (0.457 * $B + $dt + 0.5 * ($params->globina ?? 0));
            }

            $d_ = 0;
            if (!empty($params->dodatnaIzolacija)) {
                $Rn = $params->dodatnaIzolacija->debelina / $params->dodatnaIzolacija->lambda;
                $R_ = $Rn - $params->dodatnaIzolacija->debelina / $tlaLambda;
                $d_ = $R_ * $tlaLambda;

                if ($params->dodatnaIzolacija->tip == 'horizontalna') {
                    $horizontalniPsi = -$tlaLambda / Pi() * (
                        log($params->dodatnaIzolacija->dolzina / $dt + 1) -
                        log($params->dodatnaIzolacija->dolzina / ($dt + $d_) + 1)
                    );
                    $U_earth = $U_earth + 2 * $horizontalniPsi / $B;
                } elseif ($params->dodatnaIzolacija->tip == 'vertikalna') {
                    $obodniPsi = -$tlaLambda / Pi() * (
                        log(2 * $params->dodatnaIzolacija->dolzina / $dt + 1) -
                        log(2 * $params->dodatnaIzolacija->dolzina / ($dt + $d_) + 1)
                    );
                }
            }

            $Lpi = $params->povrsina * $tlaLambda / $dt *
                sqrt(2 / (pow(1 + $tlaSigma / $dt, 2) + 1));

            if (!empty($params->dodatnaIzolacija)) {
                if ($params->dodatnaIzolacija->tip == 'horizontalna') {
                    $Lpe = 0.37 * $params->obseg * $tlaLambda * (
                        (1 - exp(-$params->dodatnaIzolacija->dolzina / $tlaSigma)) *
                        log($tlaSigma / ($dt + $d_) + 1)
                        +
                        (exp(-$params->dodatnaIzolacija->dolzina / $tlaSigma) *
                        log($tlaSigma / $dt + 1))
                    );
                } elseif ($params->dodatnaIzolacija->tip == 'vertikalna') {
                    $Lpe = 0.37 * $params->obseg * $tlaLambda * (
                        (1 - exp(-2 * $params->dodatnaIzolacija->dolzina / $tlaSigma)) *
                        log($tlaSigma / ($dt + $d_) + 1) +
                        (exp(-2 * $params->dodatnaIzolacija->dolzina / $tlaSigma) *
                        log($tlaSigma / $dt + 1))
                    );
                }
            } else {
                if (!empty($params->globina)) {
                    $Lpe = 0.37 * $params->obseg * $tlaLambda *
                        exp(-$params->globina / $tlaSigma) * log($tlaSigma / $dt + 1);
                } else {
                    $Lpe = 0.37 * $params->obseg * $tlaLambda *
                        ((1 - exp(-0 / $tlaSigma)) *
                        log($tlaSigma / ($dt + $d_) + 1) +
                        (exp(-0 / $tlaSigma) *
                        log($tlaSigma / $dt + 1)));
                }
            }
        } elseif ($tip == 'stena-teren') {
            if (!isset($params->debelinaStene)) {
                throw new \Exception(sprintf('Konstrukcija %s nima podane debeline stene.', $params->id));
            }
            if (!isset($params->U_tla)) {
                throw new \Exception(sprintf('Konstrukcija %s nima podane vrednosti U_tla.', $params->id));
            }
            if (!isset($params->globina)) {
                throw new \Exception(sprintf('Konstrukcija %s nima podane globine kleti.', $params->id));
            }
            if (!isset($params->obseg)) {
                throw new \Exception(sprintf('Konstrukcija %s nima podane vrednosti obsega.', $params->id));
            }

            $d_wb = $params->debelinaStene + $tlaLambda * 1 / $U_konstrukcije;
            $d_f = $params->debelinaStene + $tlaLambda * 1 / $params->U_tla;

            if ($d_wb < $d_f) {
                $d_f = $d_wb;
            }

            $z = $params->globina;
            $U_earth = 2 * $tlaLambda / (Pi() * $z) *
                (1 + 0.5 * $d_f / ($d_f + $z)) * log($z / $d_wb + 1);

            $Lpi = $params->obseg * $params->globina *
                $tlaLambda / $d_wb *
                sqrt(2 / (pow(1 + $tlaSigma / $d_wb, 2) + 1));

            $Lpe = 0.37 * $params->obseg * $tlaLambda * (
                2 * (1 - exp(-$params->globina / $tlaSigma)) * log(($tlaSigma / $d_wb) + 1)
            );
        } elseif ($tip == 'tla-neogrevano') {
            if (!isset($params->debelinaStene)) {
                throw new \Exception(sprintf('Konstrukcija %s nima podane debeline stene.', $params->id));
            }
            if (!isset($params->U_tla)) {
                throw new \Exception(sprintf('Konstrukcija %s nima podane vrednosti U_tla.', $params->id));
            }
            if (!isset($params->visinaNadTerenom)) {
                throw new \Exception(sprintf('Konstrukcija %s nima podane višine nad terenom.', $params->id));
            }
            if (!isset($params->obseg)) {
                throw new \Exception(sprintf('Konstrukcija %s nima podanega obsega.', $params->id));
            }
            if (!isset($params->U_zid_nadTerenom)) {
                throw new \Exception(sprintf('Konstrukcija %s nima podane vrednosti U_zid_nadTerenom.', $params->id));
            }
            if (!isset($params->U_zid)) {
                throw new \Exception(sprintf('Konstrukcija %s nima podane vrednosti U_zid (zid kleti).', $params->id));
            }
            if (!isset($params->globina)) {
                throw new \Exception(sprintf('Konstrukcija %s nima podane globine kleti.', $params->id));
            }
            if (!isset($params->prostorninaKleti)) {
                throw new \Exception(sprintf('Konstrukcija %s nima podane prostornine kleti.', $params->id));
            }
            if (!isset($params->izmenjavaZraka)) {
                throw new \Exception(sprintf('Konstrukcija %s nima podane izmenjave zraka.', $params->id));
            }

            $U_earth = 1 / (1 / $U_konstrukcije + $params->povrsina / (($params->povrsina * $params->U_tla) +
                ($params->visinaNadTerenom * $params->obseg * $params->U_zid_nadTerenom) +
                ($params->globina * $params->obseg * $params->U_zid) +
                (0.34 * $params->prostorninaKleti * $params->izmenjavaZraka)));

            $d_f = $params->debelinaStene + $tlaLambda * 1 / $params->U_tla;

            $Lpi = pow(
                1 / ($params->povrsina * $U_konstrukcije) +
                1 / (
                    ($params->povrsina + $params->globina * $params->obseg) * $tlaLambda / $tlaSigma +
                    $params->visinaNadTerenom * $params->obseg * $params->U_zid_nadTerenom +
                    0.33 * $params->prostorninaKleti * $params->izmenjavaZraka
                ),
                -1
            );

            $Lpe = $params->povrsina * $U_konstrukcije * (
                    0.37 * $params->obseg * $tlaLambda * (2 - exp(-$params->globina / $tlaSigma)) *
                    log($tlaSigma / $d_f + 1) +
                    $params->visinaNadTerenom * $params->obseg * $params->U_zid_nadTerenom +
                    0.33 * $params->prostorninaKleti * $params->izmenjavaZraka
                ) / (
                    ($params->povrsina + $params->globina * $params->obseg) * $tlaLambda / $tlaSigma +
                    $params->visinaNadTerenom * $params->obseg * $params->U_zid_nadTerenom +
                    0.33 * $params->prostorninaKleti * $params->izmenjavaZraka +
                    $params->povrsina * $U_konstrukcije
                );
        }

        if ($U_earth !== null) {
            return (object)[
                'U_earth' => $U_earth,
                'obodniPsi' => $obodniPsi,
                'Lpi' => $Lpi,
                'Lpe' => $Lpe,
            ];
        }

        return null;
    }
}
