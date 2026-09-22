<?php
declare(strict_types=1);

namespace App\Calc\Hrup\OdmevniHrup;

class ModelD2
{
    /**
     * Pravokotni prostor po SIST EN 12354-6:2004, dodatek D.2.
     *
     * @param \App\Calc\Hrup\OdmevniHrup\Prostor $prostor Vhodni podatki
     * @param \stdClass $stanje Izračun absorpcije posameznih elementov
     * @param bool $zAbsorberji Obravnavano stanje
     * @return \stdClass
     */
    public function izracun(Prostor $prostor, \stdClass $stanje, bool $zAbsorberji): \stdClass
    {
        $mere = $prostor->mere;
        if ($mere === null) {
            throw new \InvalidArgumentException('Model D.2 zahteva dolžino, širino in višino prostora.');
        }
        $l = $mere->dolzina;
        $b = $mere->sirina;
        $h = $mere->visina;
        $v = $prostor->prostornina;
        if (min($l, $b, $h) <= 0 || abs($l * $b * $h - $v) > max(0.02, 0.001 * $v)) {
            throw new \InvalidArgumentException('Mere prostora D.2 niso skladne s prostornino.');
        }
        $povrsine = ['x0' => $b * $h, 'x1' => $b * $h, 'y0' => $l * $h,
            'y1' => $l * $h, 'z0' => $l * $b, 'z1' => $l * $b];
        $vsote = array_fill_keys(array_keys($povrsine), 0.0);
        $nosilci = [];
        foreach ($prostor->mejniElementi as $element) {
            /** @var \stdClass $element */
            if (!isset($element->ploskev)) {
                throw new \InvalidArgumentException('Za model D.2 manjka ploskev elementa: ' . $element->id);
            }
            $vsote[$element->ploskev] += $element->povrsina * $element->stevilo;
            $nosilci[$element->id] = $element;
        }
        foreach ($povrsine as $ploskev => $povrsina) {
            if (abs($vsote[$ploskev] - $povrsina) > max(0.02, 0.002 * $povrsina)) {
                throw new \InvalidArgumentException('Površina ploskve D.2 ni skladna z merami: ' . $ploskev);
            }
        }

        $c = $prostor->hitrostZvoka;
        $ft = 8.7 * $c / $v ** (1 / 3);
        $rezultat = (object)['prehodnaFrekvenca' => $ft, 'odmevniCas' => [], 'polja' => []];
        foreach (Prostor::FREKVENCE as $f) {
            $a = array_fill_keys(array_keys($povrsine), 0.0);
            $sipanje = $a;
            foreach ($nosilci as $element) {
                /** @var \stdClass $element */
                $s = $stanje->elementi[$element->id]['povrsina'];
                $a[$element->ploskev] += $stanje->elementi[$element->id]['absorpcijskaPovrsina'][$f];
                $sipanje[$element->ploskev] += $s * $element->sipanje[$f];
            }
            $predmeti = ['x' => 0.0, 'y' => 0.0, 'z' => 0.0, 'sredina' => 0.0];
            $elementi = array_merge($prostor->posamezniElementi, $prostor->povrsinskoPohistvo);
            foreach ($elementi as $element) {
                $polozaj = $element->polozaj ?? 'sredina';
                $predmeti[$polozaj] += $stanje->elementi[$element->id]['absorpcijskaPovrsina'][$f];
            }
            if ($zAbsorberji) {
                foreach ($prostor->absorberji as $absorber) {
                    $aa = $stanje->elementi[$absorber->id]['absorpcijskaPovrsina'][$f];
                    if (isset($nosilci[$absorber->idElementa])) {
                        $ploskev = $nosilci[$absorber->idElementa]->ploskev;
                        $a[$ploskev] += $aa;
                        $sipanje[$ploskev] += $absorber->povrsina * $absorber->sipanje[$f];
                    } else {
                        $nosilec = array_first_callback(
                            $prostor->povrsinskoPohistvo,
                            fn($e) => $e->id === $absorber->idElementa
                        );
                        $predmeti[$nosilec->polozaj ?? 'sredina'] += $aa;
                    }
                }
            }
            $m = $prostor->absorpcijaZraka[$f];
            $stevec = 55.3 / $c * $stanje->prostorninaZraka;
            if ($f < $ft) {
                // D.6 in D.9b: zmanjšana učinkovitost vsake cele ploskve.
                $aNizko = array_sum($predmeti) + 4 * $m * $v;
                foreach ($a as $ploskev => $aa) {
                    $aNizko += $aa * exp(-$aa / $povrsine[$ploskev]);
                }
                $rezultat->odmevniCas[$f] = $aNizko > 0 ? $stevec / $aNizko : null;
                $rezultat->polja[$f] = (object)['rezim' => 'nizkeFrekvence', 'Axyzd' => $aNizko];
                continue;
            }

            $osi = ['x' => [$l, $b, $h], 'y' => [$b, $l, $h], 'z' => [$h, $l, $b]];
            $n = $polja = $sklop = [];
            foreach ($osi as $os => [$mera, $u, $w]) {
                // D.2, D.3 in D.4. Sipalne površine so vsote S * delta.
                $n[$os] = 0.14 + 1.43 * (($u + $w) / (2 * $c) + pi() * $f * $u * $w / $c ** 2)
                    * $c ** 3 / (4 * pi() * $f ** 2 * $v);
                $avzporedno = $a[$os . '0'] + $a[$os . '1'];
                $polja[$os] = ($c ** 2 / (2 * $f ** 2 * $mera ** 2) * $avzporedno
                    + sqrt(2) * (array_sum($a) - $avzporedno)) * ($f / 1000) ** (1 / 3) + pi() * $m * $v;
                $sklop[$os] = array_sum($sipanje) - $sipanje[$os . '0'] - $sipanje[$os . '1']
                    + array_sum($predmeti) - $predmeti[$os];
            }
            $ad = array_sum($a) + 4 * $m * $v;
            // Algebraično enaka D.5a, brez odštevanja skoraj enakih števil.
            $stevecD = $ad + array_sum($predmeti);
            $imenovalecD = 1;
            foreach ($osi as $os => $dimenzije) {
                $vsota = $polja[$os] + $sklop[$os];
                if ($vsota > 0) {
                    $stevecD += $n[$os] * $sklop[$os] * $polja[$os] / $vsota;
                    $imenovalecD += $n[$os] * $sklop[$os] / $vsota;
                }
            }
            $astar = ['d' => $stevecD / $imenovalecD];
            $casi = ['d' => $astar['d'] > 0 ? $stevec / $astar['d'] : null];
            foreach ($osi as $os => $dimenzije) {
                $astar[$os] = $astar['d'] > 0 ? ($polja[$os] + $sklop[$os])
                    / (1 + $sklop[$os] / $astar['d']) : 0;
                $casi[$os] = $astar[$os] > 0 ? $stevec / $astar[$os] : null;
            }
            $rezultat->odmevniCas[$f] = in_array(null, $casi, true) ? null : max(array_sum($casi) / 4, $casi['d']);
            $rezultat->polja[$f] = (object)[
                'rezim' => 'visokeFrekvence', 'N' => $n, 'A' => $polja, 'Asipanje' => $sklop,
                'Aefektivna' => $astar, 'T' => $casi,
            ];
        }

        return $rezultat;
    }
}
