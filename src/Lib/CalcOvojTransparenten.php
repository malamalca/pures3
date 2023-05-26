<?php
declare(strict_types=1);

namespace App\Lib;

use App\Core\Configure;
use App\Core\Log;

class CalcOvojTransparenten
{
    /**
     * Glavna funkcija za analizo transparentnega dela oboja
     *
     * @param \stdClass $cona Cona
     * @param \stdClass $okolje Parametri okolja
     * @param array $konstrukcije Seznam konstrukcij
     * @return void
     */
    public static function analiza($cona, $okolje, $konstrukcije)
    {
        $konstrukcije = array_combine(array_map(fn($k) => $k->id, $konstrukcije), $konstrukcije);

        if (isset($cona->ovoj->transparentneKonstrukcije)) {
            foreach ($cona->ovoj->transparentneKonstrukcije as $ix => $elementOvoja) {
                if (!isset($konstrukcije[$elementOvoja->idKonstrukcije])) {
                    throw new \Exception(sprintf('Konstrukcija "%1$s" ne obstaja', $elementOvoja->idKonstrukcije));
                }
                $kons = $konstrukcije[$elementOvoja->idKonstrukcije];

                $elementOvoja->stevilo = $elementOvoja->stevilo ?? 1;

                $elementOvoja->orientacija = $elementOvoja->orientacija ?? '';
                $elementOvoja->naklon = $elementOvoja->naklon ?? 0;
                $elementOvoja->povrsina = $elementOvoja->povrsina ?? 0;

                $elementOvoja->delezOkvirja = $elementOvoja->delezOkvirja ?? 1;

                // dvoslojna zasteklitev 0.67; troslojna zasteklitev 0.5
                $elementOvoja->g = $kons->g ?? 0.5;
                $elementOvoja->faktorSencil = $elementOvoja->faktorSencil ?? 1;

                $elementOvoja->g_sh = $elementOvoja->g * $elementOvoja->faktorSencil;

                switch ($kons->vrsta) {
                    case '1':
                    case '2':
                        // 1 - okna
                        // 2 - strešna okna
                        $elementOvoja->U = (
                            $elementOvoja->povrsina * $kons->Ug * (1 - ($elementOvoja->delezOkvirja ?? 0)) +
                            $elementOvoja->povrsina * $kons->Uf * ($elementOvoja->delezOkvirja ?? 0) +
                            $elementOvoja->dolzinaOkvirja * $kons->Psi
                            ) / $elementOvoja->povrsina;
                        break;
                    case '3':
                    case '4':
                        // 3 - vrata
                        // 4 - garažna vrata ali proti neogrevavanem prostoru
                        $elementOvoja->U = $kons->Ud;
                        break;
                    default:
                        throw new \Exception(sprintf(
                            'Tip transparentne konstrukcije %1$s ne obstaja!',
                            $elementOvoja->idKonstrukcije
                        ));
                }

                // temperaturni korekcijski faktor
                $elementOvoja->b = 1;

                // faktor sončnega sevanja
                foreach ($okolje->obsevanje as $line) {
                    if ($line->orientacija == $elementOvoja->orientacija && $line->naklon == $elementOvoja->naklon) {
                        $elementOvoja->soncnoObsevanje = $line->obsevanje;
                        break;
                    }
                }
                if (empty($elementOvoja->soncnoObsevanje)) {
                    Log::warn('Sončno obsevanje za element ne obstaja', ['id' => $elementOvoja->id]);
                }

                $pomozniFaktorji = Configure::read('lookups.transparentne.pomozniFaktorji');
                $A1 = $pomozniFaktorji['nadstresek']['A1'][$elementOvoja->orientacija];
                $A2 = $pomozniFaktorji['nadstresek']['A2'][$elementOvoja->orientacija];
                $B1 = $pomozniFaktorji['nadstresek']['B1'][$elementOvoja->orientacija];
                $B2 = $pomozniFaktorji['nadstresek']['B2'][$elementOvoja->orientacija];

                $A1_stena = $pomozniFaktorji['stena']['A1'][$elementOvoja->orientacija];
                $A2_stena = $pomozniFaktorji['stena']['A2'][$elementOvoja->orientacija];
                $B1_stena = $pomozniFaktorji['stena']['B1'][$elementOvoja->orientacija];
                $B2_stena = $pomozniFaktorji['stena']['B2'][$elementOvoja->orientacija];

                $faktorOrientacije = Configure::read('lookups.transparentne.faktorOrientacije.' .
                    $elementOvoja->orientacija);

                $visineSonca = Configure::read('lookups.transparentne.visinaSonca');
                $faktorjiSencenjaOvir = Configure::read('lookups.transparentne.faktorjiSencenja');

                foreach (array_keys(Calc::MESECI) as $mesec) {
                    $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

                    /** ============================================================================================= */
                    /** 1. Senčenje nadstreška in stranskih ovir */

                    $D_ovh = $elementOvoja->stranskoSencenje->zgorajDolzina ?? 0;
                    $L_ovh = $elementOvoja->stranskoSencenje->zgorajRazdalja ?? 0;

                    $D_stena_l = $elementOvoja->stranskoSencenje->levoDolzina ?? 0;
                    $L_stena_l = $elementOvoja->stranskoSencenje->levoRazdalja ?? 0;

                    $D_stena_d = $elementOvoja->stranskoSencenje->desnoDolzina ?? 0;
                    $L_stena_d = $elementOvoja->stranskoSencenje->desnoRazdalja ?? 0;

                    $W = $elementOvoja->sirinaStekla ?? 0;
                    $H = $elementOvoja->visinaStekla ?? 0;
                    $zemljepisnaSirina = 40;
                    $deklinacija = Configure::read('lookups.transparentne.mesecnaDeklinacija.' . $mesec);

                    $delezObsevanja = Configure::read('lookups.transparentne.delezObsevanja.' .
                        $elementOvoja->orientacija . '.' . $mesec) / 100;

                    $P1_ovh = $H ? $D_ovh / $H : 0;
                    $P2_ovh = $H ? $L_ovh / $H : 0;

                    $P1_stena_l = $W ? $D_stena_l / $W : 0;
                    $P2_stena_l = $H ? $L_stena_l / $H : 0;

                    $P1_stena_d = $W ? $D_stena_d / $W : 0;
                    $P2_stena_d = $H ? $L_stena_d / $H : 0;

                    // dolžina sence nadstreška
                    // po standardu je malo drugačna enačba:
                    // TODO: preveri razlike
                    // $h_ovh = $H - $H * (($A1 + $B1 * ($zemljepisnaSirina - $deklinacija)) * $P1_ovh +
                    //    ($A2 + $B2 * ($zemljepisnaSirina - $deklinacija)) * $P1_ovh * $P2_ovh);

                    $h_ovh = $H - $H * (1 + (($A1 + $B1 * ($zemljepisnaSirina - $deklinacija)) * $P1_ovh +
                        ($A2 + $B2 * ($zemljepisnaSirina - $deklinacija)) * $P1_ovh * $P2_ovh));

                    $h_ovh = $h_ovh > $H ? $H : ($h_ovh < 0 ? 0 : $h_ovh);

                    $w_fin_l = $W -
                        $W * (1 + (($A1_stena + $B1_stena * ($zemljepisnaSirina - $deklinacija)) * $P1_stena_l +
                        ($A2_stena + $B2_stena * ($zemljepisnaSirina - $deklinacija)) * $P1_stena_l * $P2_stena_l));

                    $w_fin_l = ($w_fin_l > $W ? $W : ($w_fin_l < 0 ? 0 : $w_fin_l)) * $faktorOrientacije['l'];

                    $w_fin_d = $W -
                        $W * (1 + (($A1_stena + $B1_stena * ($zemljepisnaSirina - $deklinacija)) * $P1_stena_d +
                        ($A2_stena + $B2_stena * ($zemljepisnaSirina - $deklinacija)) * $P1_stena_d * $P2_stena_d));

                    $w_fin_d = ($w_fin_d > $W ? $W : ($w_fin_d < 0 ? 0 : $w_fin_d)) * $faktorOrientacije['d'];

                    $w_fin = $w_fin_l + $w_fin_d < 0 ? 0 : $w_fin_l + $w_fin_d;

                    $Fsh_ov = $H * $W > 0 ? ($H - $h_ovh) * ($W - $w_fin) / ($H * $W) : 0;

                    /** ============================================================================================= */
                    /** 2. Senčenje drugih objektov */
                    $h_k_skupaj = 0;
                    if (!empty($elementOvoja->sencenjeOvir)) {
                        $h_k_skupaj = 0;
                        foreach ($elementOvoja->sencenjeOvir as $ovira) {
                            $visinskiKot =
                                atan(($ovira->visinaOvire - $ovira->visinaNadTerenom) / $ovira->oddaljenostOvire)
                                * 180 / pi();

                            $visinaSonca = $visineSonca[$elementOvoja->orientacija][$ovira->kvadrant][$mesec];

                            $h_k_obst = $ovira->visinaOvire - $ovira->visinaNadTerenom -
                                $ovira->oddaljenostOvire * tan(deg2rad($visinaSonca));
                            if ($h_k_obst < 0) {
                                $h_k_obst = 0;
                            }
                            if ($h_k_obst > $H) {
                                $h_k_obst = $H;
                            }

                            $obdobje = $mesec > 4 && $mesec < 9 ? 'hlajenje' : 'ogrevanje';
                            $h_k_skupaj += $h_k_obst *
                                $faktorjiSencenjaOvir[$elementOvoja->orientacija][$ovira->kvadrant][$obdobje];
                        }

                        $Fsh_obst = ($H - $h_k_skupaj) * $W / ($W * $H);
                    }

                    /** ============================================================================================= */
                    /** Skupni faktor senčenja */
                    /** Celice AH27:AS27 */
                    $h_ovh = $h_ovh + $h_k_skupaj;
                    if ($h_ovh > $H) {
                        $h_ovh = $H;
                    }

                    /* celice AH63:AS63 */
                    $Fsh = $H * $W > 0 ? ($H - $h_ovh) * ($W - $w_fin) / ($W * $H) : 0;

                    $elementOvoja->faktorSencenja[$mesec] = 1 - $delezObsevanja + $Fsh * $delezObsevanja;

                    // izračun solarnih dobitkov
                    $alphaSr = 0.3;
                    $Fsky = $elementOvoja->naklon < 45 ? 1 : 0.5;
                    $hri = 4.14;
                    $dTsky = 11;
                    $Rse = 0.04;
                    $Fic = 0.9; // faktor vpadnega kota. TSG stran 71

                    // mesečna prehodnost sevanja zaradi zasteklitve s senčili
                    $g = $kons->g ?? 0;
                    $g_sh_ogrevanje = 1;
                    $g_sh_hlajenje = $g * ($elementOvoja->faktorSencil ?? 0);

                    // sevanje elementa proti nebu za trenutni mesec
                    $Qsol_ogrevanje = $g * $Fic * $elementOvoja->povrsina * (1 - $elementOvoja->delezOkvirja) *
                        $elementOvoja->faktorSencenja[$mesec] * $g_sh_ogrevanje *
                        $elementOvoja->soncnoObsevanje[$mesec] * $stDni;
                    $Qsol_hlajenje = $g * $Fic * $elementOvoja->povrsina * (1 - $elementOvoja->delezOkvirja) *
                        $elementOvoja->faktorSencenja[$mesec] * $g_sh_hlajenje *
                        $elementOvoja->soncnoObsevanje[$mesec] * $stDni;

                    $Qsky = 0.001 * $Fsky * $Rse * ($elementOvoja->U + $cona->deltaPsi) * $elementOvoja->povrsina *
                        $hri * $dTsky * $stDni * 24;

                    $elementOvoja->solarniDobitkiOgrevanje[$mesec] = ($Qsol_ogrevanje - $Qsky) / 1000;
                    $elementOvoja->solarniDobitkiHlajenje[$mesec] = ($Qsol_hlajenje - $Qsky) / 1000;

                    $cona->solarniDobitkiOgrevanje[$mesec] +=
                        $elementOvoja->solarniDobitkiOgrevanje[$mesec] * $elementOvoja->stevilo;
                    $cona->solarniDobitkiHlajenje[$mesec] +=
                        $elementOvoja->solarniDobitkiHlajenje[$mesec] * $elementOvoja->stevilo;

                    // transmisijske izgube
                    $elementOvoja->transIzgubeOgrevanje[$mesec] = ($elementOvoja->U + $cona->deltaPsi) *
                        $elementOvoja->povrsina * $elementOvoja->b * 24 / 1000 *
                        $cona->deltaTOgrevanje[$mesec] * $stDni;

                    $elementOvoja->transIzgubeHlajenje[$mesec] = ($elementOvoja->U + $cona->deltaPsi) *
                        $elementOvoja->povrsina * $elementOvoja->b * 24 / 1000 *
                        $cona->deltaTHlajenje[$mesec] * $stDni;

                    $cona->transIzgubeOgrevanje[$mesec] +=
                        $elementOvoja->transIzgubeOgrevanje[$mesec] * $elementOvoja->stevilo;
                    $cona->transIzgubeHlajenje[$mesec] +=
                        $elementOvoja->transIzgubeHlajenje[$mesec] * $elementOvoja->stevilo;
                }
            }
        }
    }
}
