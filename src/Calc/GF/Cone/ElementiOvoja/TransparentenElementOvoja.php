<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\ElementiOvoja;

use App\Core\Configure;
use App\Lib\Calc;
use App\Lib\EvalMath;

class TransparentenElementOvoja extends ElementOvoja
{
    public float $delezOkvirja = 0;
    public float $dolzinaOkvirja = 0;

    public float $visinaStekla = 0;
    public float $sirinaStekla = 0;

    // lahko se overrida določilo iz TSG
    public bool $dobitekSS = true;

    public float $g = 0;
    public float $faktorSencil = 0;
    public float $g_sh = 0;

    public array $sencenjeOvir;
    public StranskoSencenje $stranskoSencenje;

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

        $EvalMath = EvalMath::getInstance(['decimalSeparator' => '.', 'thousandsSeparator' => '']);

        $numSettings = ['A', 'B', 'sirinaOkvirja', 'sirinaStekla', 'visinaStekla', 'delezOkvirja', 'dolzinaOkvirja'];
        foreach ($numSettings as $setting) {
            if (isset($config->$setting) && gettype($config->$setting) == 'string') {
                $config->$setting = (float)$EvalMath->e($config->$setting);
                $EvalMath->setVar($setting, $config->$setting);
            }
        }

        /** Povzemi lastnosti zidu, v katerega je okno vgrajeno */
        if (isset($this->options['elementVgradnje'])) {
            /** @var \App\Calc\GF\Cone\ElementiOvoja\NetransparentenElementOvoja $ntElement */
            $ntElement = $this->options['elementVgradnje'];

            if (empty($config->naklon)) {
                $this->naklon = $ntElement->naklon;
            }
            if (empty($config->orientacija)) {
                $this->orientacija = $ntElement->orientacija;
            }
        }

        if (isset($config->dobitekSS)) {
            $this->dobitekSS = (bool)$config->dobitekSS;
        }

        /** @var \stdClass $config */
        if (!empty($config->A) && !empty($config->B) && !empty($config->sirinaOkvirja)) {
            $config->sirinaStekla = $config->A - $config->sirinaOkvirja * 2;
            $config->visinaStekla = $config->B - $config->sirinaOkvirja * 2;
            $config->dolzinaOkvirja = 2 * $config->A + 2 * $config->B;
        }

        if (empty($config->delezOkvirja) && !empty($config->visinaStekla) && !empty($config->sirinaStekla)) {
            $this->delezOkvirja = 1 - ($config->sirinaStekla * $config->visinaStekla / $this->povrsina);
        } else {
            $this->delezOkvirja = $config->delezOkvirja ?? 1;
        }

        $this->delezOkvirja = !empty($this->options['referencnaStavba']) ? 0.25 : $this->delezOkvirja;

        $this->dolzinaOkvirja = $config->dolzinaOkvirja ?? 1;

        // dvoslojna zasteklitev 0.67; troslojna zasteklitev 0.5
        $this->g = !empty($this->options['referencnaStavba']) ? 0.5 : ($this->konstrukcija->g ?? 0.5);
        $this->faktorSencil = !empty($this->options['referencnaStavba']) ? 0.3 : ($config->faktorSencil ?? 1);

        $this->g_sh = $this->g * $this->faktorSencil;

        $this->sirinaStekla = $config->sirinaStekla ?? 0;
        $this->visinaStekla = $config->visinaStekla ?? 0;

        if (!empty($config->sencenjeOvir)) {
            $this->sencenjeOvir = $config->sencenjeOvir;
        }

        $this->stranskoSencenje = new StranskoSencenje();
        if (!empty($this->konstrukcija->stranskoSencenje)) {
            $this->stranskoSencenje->merge($this->konstrukcija->stranskoSencenje);
        }

        if (!empty($config->stranskoSencenje)) {
            $this->stranskoSencenje->merge($config->stranskoSencenje);
        }

        switch ($this->konstrukcija->vrsta) {
            case '0':
            case '1':
                // 0 - okna
                // 1 - strešna okna
                if (!empty($this->konstrukcija->Uw)) {
                    $this->U = $this->povrsina * $this->konstrukcija->Uw / $this->povrsina;
                } else {
                    $this->U = (
                        $this->povrsina * $this->konstrukcija->Ug * (1 - $this->delezOkvirja) +
                        $this->povrsina * $this->konstrukcija->Uf * $this->delezOkvirja +
                        $this->dolzinaOkvirja * $this->konstrukcija->Psi
                        ) / $this->povrsina;
                }
                break;
            case '2':
            case '3':
                // 2 - vrata
                // 3 - garažna vrata ali proti neogrevavanem prostoru
                $this->U = $this->konstrukcija->Ud;
                break;
        }

        if (empty($this->orientacija) || empty($this->naklon)) {
            throw new \Exception(sprintf('Transparentni element %s nima določene orientacije ali naklona', $this->id));
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

            if ($this->dobitekSS) {
                /** ============================================================================================= */
                /** 1. Senčenje nadstreška in stranskih ovir */

                $D_ovh = $this->stranskoSencenje->zgorajDolzina;
                $L_ovh = $this->stranskoSencenje->zgorajRazdalja;

                $D_stena_l = $this->stranskoSencenje->levoDolzina;
                $L_stena_l = $this->stranskoSencenje->levoRazdalja;

                $D_stena_d = $this->stranskoSencenje->desnoDolzina;
                $L_stena_d = $this->stranskoSencenje->desnoRazdalja;

                $W = $this->sirinaStekla;
                $H = $this->visinaStekla;
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
                $Fsky = $this->naklon < 45 ? 1 : 0.5;
                $hri = 4.14;
                $dTsky = 11;
                $Rse = 0.04;
                $Fic = 0.9; // faktor vpadnega kota. TSG stran 71

                // mesečna prehodnost sevanja zaradi zasteklitve s senčili
                $g = $this->g;
                $g_sh_ogrevanje = $g;
                $g_sh_hlajenje = $g * $this->faktorSencil;

                // sevanje elementa proti nebu za trenutni mesec
                $Qsol_ogrevanje = $g_sh_ogrevanje * $Fic * $this->povrsina * (1 - $this->delezOkvirja) *
                    $this->faktorSencenja[$mesec] * $this->soncnoObsevanje[$mesec] * $stDni;
                $Qsol_hlajenje = $g_sh_hlajenje * $Fic * $this->povrsina * (1 - $this->delezOkvirja) *
                    $this->faktorSencenja[$mesec] * $this->soncnoObsevanje[$mesec] * $stDni;

                $Qsky = $Fsky * $Rse * ($this->U + $cona->deltaPsi) * $this->povrsina *
                    $hri * $dTsky * $stDni * 24;

                $this->solarniDobitkiOgrevanje[$mesec] = ($Qsol_ogrevanje - $Qsky) / 1000 * $this->stevilo;
                $this->solarniDobitkiHlajenje[$mesec] = ($Qsol_hlajenje - $Qsky) / 1000 * $this->stevilo;
            } else {
                $this->solarniDobitkiOgrevanje[$mesec] = 0;
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

        $elementOvoja->dobitekSS = $this->dobitekSS;

        $elementOvoja->g = $this->g;
        $elementOvoja->faktorSencil = $this->faktorSencil;
        $elementOvoja->g_sh = $this->g_sh;

        $elementOvoja->stranskoSencenje = $this->stranskoSencenje;

        if (isset($elementOvoja->sencenjeOvir)) {
            $elementOvoja->sencenjeOvir = $this->sencenjeOvir;
        }

        return $elementOvoja;
    }
}
