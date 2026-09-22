<?php
declare(strict_types=1);

namespace App\Calc\Hrup\OdmevniHrup;

class Zahteve
{
    /**
     * TSG-1-005:2012, poglavje 7.3, diagrami 2–5 in preglednica 12.
     *
     * @param \App\Calc\Hrup\OdmevniHrup\Prostor $prostor Obravnavani prostor
     * @return \stdClass
     */
    public function preveri(Prostor $prostor): \stdClass
    {
        if ($prostor->vrsta === 'lastna') {
            return $this->lastnaMeja($prostor);
        }
        $v = $prostor->prostornina;
        $topt = null;
        $opombe = [];
        $ucilnica = in_array($prostor->vrsta, ['ucilnica', 'glasbenaUcilnica']);
        switch ($prostor->vrsta) {
            case 'ucilnica':
                $topt = 0.32 * log10($v) - 0.17;
                break;
            case 'glasbenaUcilnica':
                // Približek premice iz diagrama 3; diagram nima zapisane enačbe.
                if ($v >= 30 && $v <= 30000) {
                    $topt = 0.45 * log10($v) + 0.07;
                }
                $opombe[] = 'Optimalni čas je približek diagrama 3 (0,45 log₁₀ V + 0,07).';
                break;
            case 'sportniProstor1':
                $topt = 1.27 * log10($v) - 2.49;
                break;
            case 'sportniProstor2':
                $topt = 0.95 * log10($v) - 1.74;
                break;
            default:
                $tabele = [
                    'vecnamenskiPouk' => [0.5, 0.6, 0.8, 0.9],
                    'vecnamenskiGovor' => [0.7, 0.8, 0.9, 1.0],
                    'vecnamenskiGlasba' => [1.1, 1.3, 1.4, 1.5],
                ];
                $volumni = [200, 400, 800, 1600];
                if ($v >= 200 && $v <= 1600) {
                    for ($i = 1; $i < 4; $i++) {
                        $razpon = $volumni[$i] - $volumni[$i - 1];
                        if ($v <= $volumni[$i] && $razpon > 0) {
                            $tabela = $tabele[$prostor->vrsta];
                            $delez = ($v - $volumni[$i - 1]) / $razpon;
                            $topt = round($tabela[$i - 1] + $delez * ($tabela[$i] - $tabela[$i - 1]), 1);
                            break;
                        }
                    }
                }
        }
        if ($topt !== null && $topt <= 0) {
            $topt = null;
        }
        if ($topt === null) {
            $opombe[] = 'Za to prostornino ni določljivega pozitivnega cilja; ekstrapolacija se ne izvaja.';
        }
        $zahtevanaZasedenost = $ucilnica ? 'polna' : (
            str_starts_with($prostor->vrsta, 'sportni') ? 'prazna' : null
        );
        $zasedenostUstreza = $zahtevanaZasedenost === null || $prostor->zasedenost === $zahtevanaZasedenost;
        if ($zahtevanaZasedenost === 'prazna') {
            foreach (array_merge($prostor->posamezniElementi, $prostor->povrsinskoPohistvo) as $element) {
                if (($element->vrsta ?? 'pohistvo') === 'osebe' && $element->stevilo > 0) {
                    $zasedenostUstreza = false;
                }
            }
        }
        if (!$zasedenostUstreza) {
            $opombe[] = 'Za presojo je zahtevana zasedenost: ' . $zahtevanaZasedenost . '.';
        }
        $pasovi = [];
        $ustreza = $topt !== null && $zasedenostUstreza ? true : null;
        if ($ucilnica) {
            // Odčitane oktavne vrednosti diagramov 2 in 4 (približek grafičnih mej).
            $min = $prostor->vrsta == 'ucilnica' ? [0.65, 0.8, 0.8, 0.8, 0.8, 0.6] : [1, 0.8, 0.8, 0.8, 0.8, 0.6];
            $max = $prostor->vrsta == 'ucilnica' ? [1.2, 1.2, 1.2, 1.2, 1.2, 1.2] : [1.4, 1.2, 1.2, 1.2, 1.2, 1.2];
            $opombe[] = 'Tolerančne meje po oktavah so odčitane iz diagrama ' .
                ($prostor->vrsta == 'ucilnica' ? '2' : '4') . '.';
            foreach (Prostor::FREKVENCE as $i => $frekvenca) {
                $t = $prostor->po->odmevniCas[$frekvenca] ?? null;
                $spodnja = $topt !== null ? $min[$i] * $topt : null;
                $zgornja = $topt !== null ? $max[$i] * $topt : null;
                $ok = $zasedenostUstreza && $t !== null && $spodnja !== null && $zgornja !== null
                    ? $t >= $spodnja - 1e-9 && $t <= $zgornja + 1e-9 : null;
                $pasovi[$frekvenca] = (object)['min' => $spodnja, 'max' => $zgornja, 'ustreza' => $ok];
            }
            $ustreza = $this->zdruziPasove($pasovi);
            $kriterij = 'Tolerančni pas TSG po oktavah 125–4000 Hz.';
        } else {
            $kriterij = 'Projektni kriterij: srednji čas pri 500 in 1000 Hz ne presega optimalnega časa TSG.';
            $t = $prostor->po->srednjiOdmevniCas ?? null;
            $ustreza = $ustreza !== null && $t !== null ? $t <= $topt + 1e-9 : null;
            $opombe[] = 'TSG podaja optimalni čas; uporaba kot zgornja meja je izbrani projektni kriterij.';
        }

        return (object)[
            'vir' => 'TSG-1-005:2012, 7.3', 'optimalniCas' => $topt,
            'kriterij' => $kriterij, 'zahtevanaZasedenost' => $zahtevanaZasedenost,
            'pasovi' => $pasovi, 'ustreza' => $ustreza, 'opombe' => $opombe,
            'odstopanje' => $topt !== null && isset($prostor->po->srednjiOdmevniCas)
                ? $prostor->po->srednjiOdmevniCas - $topt : null,
        ];
    }

    /**
     * Lasten tip prostora in njegova projektna meja, ne normativna meja TSG.
     *
     * @param \App\Calc\Hrup\OdmevniHrup\Prostor $prostor Obravnavani prostor
     * @return \stdClass
     */
    private function lastnaMeja(Prostor $prostor): \stdClass
    {
        $meja = $prostor->mejniOdmevniCas;
        $t = $prostor->po->srednjiOdmevniCas ?? null;
        $pasovi = [];
        $ustreza = null;
        if (is_array($meja)) {
            $ustreza = true;
            foreach (Prostor::FREKVENCE as $f) {
                $cas = $prostor->po->odmevniCas[$f] ?? null;
                $ok = $cas === null ? null : $cas <= $meja[$f] + 1e-9;
                $pasovi[$f] = (object)['min' => null, 'max' => $meja[$f], 'ustreza' => $ok];
            }
            $ustreza = $this->zdruziPasove($pasovi);
            $kriterij = 'Lastne zgornje meje po oktavah.';
            $mejnaVrednost = null;
        } else {
            $ustreza = $t !== null && $meja !== null ? $t <= $meja + 1e-9 : null;
            $kriterij = 'Lastna zgornja meja srednjega časa pri 500 in 1000 Hz.';
            $mejnaVrednost = $meja;
        }

        return (object)[
            'vir' => 'Projektni kriterij: ' . $prostor->nazivVrste, 'optimalniCas' => null,
            'mejnaVrednost' => $mejnaVrednost, 'kriterij' => $kriterij, 'zahtevanaZasedenost' => null,
            'pasovi' => $pasovi, 'ustreza' => $ustreza, 'opombe' => [],
            'odstopanje' => $t !== null && $mejnaVrednost !== null ? $t - $mejnaVrednost : null,
        ];
    }

    /**
     * Znana prekoračitev pomeni neskladnost tudi ob nedoločljivem drugem pasu.
     *
     * @param array $pasovi Rezultati po oktavah
     * @return bool|null
     */
    private function zdruziPasove(array $pasovi): ?bool
    {
        $rezultati = array_map(fn($pas) => $pas->ustreza, $pasovi);
        if (in_array(false, $rezultati, true)) {
            return false;
        }

        return in_array(null, $rezultati, true) ? null : true;
    }
}
