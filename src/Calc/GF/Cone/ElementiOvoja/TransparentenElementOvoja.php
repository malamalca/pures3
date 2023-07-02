<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\ElementiOvoja;

use App\Core\Configure;
use App\Lib\Calc;

class TransparentenElementOvoja extends ElementOvoja
{
    public float $delezOkvirja = 0;
    public float $dolzinaOkvirja = 0;

    public float $visinaStekla = 0;
    public float $sirinaStekla = 0;

    public float $g = 0;
    public float $faktorSencil = 0;
    public float $g_sh = 0;

    public array $sencenjeOvir;
    public \stdClass $stranskoSencenje;

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

        if (empty($config->delezOkvirja) && !empty($config->dolzinaStekla) && !empty($config->sirinaStekla)) {
            $this->delezOkvirja = 1 - ($config->sirinaStekla * $config->dolzinaStekla / $config->povrsina);
        } else {
            $this->delezOkvirja = $config->delezOkvirja ?? 1;
        }
        $this->dolzinaOkvirja = $config->dolzinaOkvirja ?? 1;

        // dvoslojna zasteklitev 0.67; troslojna zasteklitev 0.5
        $this->g = $this->konstrukcija->g ?? 0.5;
        $this->faktorSencil = $config->faktorSencil ?? 1;

        $this->g_sh = $this->g * $this->faktorSencil;

        $this->sirinaStekla = $config->sirinaStekla ?? 0;
        $this->visinaStekla = $config->visinaStekla ?? 0;

        if (!empty($config->sencenjeOvir)) {
            $this->sencenjeOvir = $config->sencenjeOvir;
        }

        if (!empty($config->stranskoSencenje)) {
            $this->stranskoSencenje = $config->stranskoSencenje;
        }

        switch ($this->konstrukcija->vrsta) {
            case '0':
            case '1':
                // 0 - okna
                // 1 - strešna okna
                $this->U = (
                    $this->povrsina * $this->konstrukcija->Ug * (1 - $this->delezOkvirja) +
                    $this->povrsina * $this->konstrukcija->Uf * $this->delezOkvirja +
                    $this->dolzinaOkvirja * $this->konstrukcija->Psi
                    ) / $this->povrsina;
                break;
            case '2':
            case '3':
                // 2 - vrata
                // 3 - garažna vrata ali proti neogrevavanem prostoru
                $this->U = $this->konstrukcija->Ud;
                break;
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

        $pomozniFaktorji = Configure::read('lookups.transparentne.pomozniFaktorji');
        $A1 = $pomozniFaktorji['nadstresek']['A1'][$this->orientacija];
        $A2 = $pomozniFaktorji['nadstresek']['A2'][$this->orientacija];
        $B1 = $pomozniFaktorji['nadstresek']['B1'][$this->orientacija];
        $B2 = $pomozniFaktorji['nadstresek']['B2'][$this->orientacija];

        $A1_stena = $pomozniFaktorji['stena']['A1'][$this->orientacija];
        $A2_stena = $pomozniFaktorji['stena']['A2'][$this->orientacija];
        $B1_stena = $pomozniFaktorji['stena']['B1'][$this->orientacija];
        $B2_stena = $pomozniFaktorji['stena']['B2'][$this->orientacija];

        $faktorOrientacije = Configure::read('lookups.transparentne.faktorOrientacije.' .
            $this->orientacija);

        $visineSonca = Configure::read('lookups.transparentne.visinaSonca');
        $faktorjiSencenjaOvir = Configure::read('lookups.transparentne.faktorjiSencenja');

        $this->H_ogrevanje = ($this->U + $cona->deltaPsi) * $this->povrsina * $this->b * $this->stevilo;
        $this->H_hlajenje = ($this->U + $cona->deltaPsi) * $this->povrsina * $this->b * $this->stevilo;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            /** ============================================================================================= */
            /** 1. Senčenje nadstreška in stranskih ovir */

            $D_ovh = $this->stranskoSencenje->zgorajDolzina ?? 0;
            $L_ovh = $this->stranskoSencenje->zgorajRazdalja ?? 0;

            $D_stena_l = $this->stranskoSencenje->levoDolzina ?? 0;
            $L_stena_l = $this->stranskoSencenje->levoRazdalja ?? 0;

            $D_stena_d = $this->stranskoSencenje->desnoDolzina ?? 0;
            $L_stena_d = $this->stranskoSencenje->desnoRazdalja ?? 0;

            $W = $this->sirinaStekla ?? 0;
            $H = $this->visinaStekla ?? 0;
            $zemljepisnaSirina = 40;
            $deklinacija = Configure::read('lookups.transparentne.mesecnaDeklinacija.' . $mesec);

            $delezObsevanja = Configure::read('lookups.transparentne.delezObsevanja.' .
                $this->orientacija . '.' . $mesec) / 100;

            $P1_ovh = $H ? $D_ovh / $H : 0;
            $P2_ovh = $H ? $L_ovh / $H : 0;

            $P1_stena_l = $W ? $D_stena_l / $W : 0;
            $P2_stena_l = $W ? $L_stena_l / $W : 0;

            $P1_stena_d = $W ? $D_stena_d / $W : 0;
            $P2_stena_d = $W ? $L_stena_d / $W : 0;

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
            if (!empty($this->sencenjeOvir)) {
                $h_k_skupaj = 0;
                foreach ($this->sencenjeOvir as $ovira) {
                    $visinskiKot =
                        atan(($ovira->visinaOvire - $ovira->visinaNadTerenom) / $ovira->oddaljenostOvire)
                        * 180 / pi();

                    $visinaSonca = $visineSonca[$this->orientacija][$ovira->kvadrant][$mesec];

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
                        $faktorjiSencenjaOvir[$this->orientacija][$ovira->kvadrant][$obdobje];
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

            $this->faktorSencenja[$mesec] = 1 - $delezObsevanja + $Fsh * $delezObsevanja;

            // izračun solarnih dobitkov
            $alphaSr = 0.3;
            $Fsky = $this->naklon < 45 ? 0.5 : 1;
            $hri = 4.14;
            $dTsky = 11;
            $Rse = 0.04;
            $Fic = 0.9; // faktor vpadnega kota. TSG stran 71

            // mesečna prehodnost sevanja zaradi zasteklitve s senčili
            $g = $this->g ?? 0;
            $g_sh_ogrevanje = 1;
            $g_sh_hlajenje = $g * ($this->faktorSencil ?? 0);

            // sevanje elementa proti nebu za trenutni mesec
            $Qsol_ogrevanje = $g * $Fic * $this->povrsina * (1 - $this->delezOkvirja) *
                $this->faktorSencenja[$mesec] * $g_sh_ogrevanje *
                $this->soncnoObsevanje[$mesec] * $stDni;
            $Qsol_hlajenje = $g * $Fic * $this->povrsina * (1 - $this->delezOkvirja) *
                $this->faktorSencenja[$mesec] * $g_sh_hlajenje *
                $this->soncnoObsevanje[$mesec] * $stDni;

            $Qsky = 0.001 * $Fsky * $Rse * ($this->U + $cona->deltaPsi) * $this->povrsina *
                $hri * $dTsky * $stDni * 24;

            $this->solarniDobitkiOgrevanje[$mesec] = ($Qsol_ogrevanje - $Qsky) / 1000 * $this->stevilo;
            if ($this->solarniDobitkiOgrevanje[$mesec] < 0) {
                $this->solarniDobitkiOgrevanje[$mesec] = 0;
            }

            $this->solarniDobitkiHlajenje[$mesec] = ($Qsol_hlajenje - $Qsky) / 1000 * $this->stevilo;
            if ($this->solarniDobitkiHlajenje[$mesec] < 0) {
                $this->solarniDobitkiHlajenje[$mesec] = 0;
            }

            // transmisijske izgube
            $this->transIzgubeOgrevanje[$mesec] = $this->H_ogrevanje * 24 / 1000 *
                    $cona->deltaTOgrevanje[$mesec] * $stDni;

            $this->transIzgubeHlajenje[$mesec] = $this->H_hlajenje * 24 / 1000 *
                $cona->deltaTHlajenje[$mesec] * $stDni;
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

        $elementOvoja->delezOkvirja = $this->delezOkvirja;
        $elementOvoja->dolzinaOkvirja = $this->dolzinaOkvirja;

        $elementOvoja->sirinaStekla = $this->sirinaStekla;
        $elementOvoja->visinaStekla = $this->visinaStekla;

        $elementOvoja->g = $this->g;
        $elementOvoja->faktorSencil = $this->faktorSencil;
        $elementOvoja->g_sh = $this->g_sh;

        $elementOvoja->sencenjeOvir = $this->sencenjeOvir;

        return $elementOvoja;
    }
}
