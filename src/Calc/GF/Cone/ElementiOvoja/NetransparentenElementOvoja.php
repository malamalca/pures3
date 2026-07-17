<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\ElementiOvoja;

use App\Calc\GF\Cone\ElementiOvoja\Izbire\BarvaElementaOvoja;
use App\Calc\GF\Cone\ElementiOvoja\Izbire\VrstaTal;
use App\Core\Log;
use App\Lib\Calc;
use App\Lib\EvalMath;

class NetransparentenElementOvoja extends ElementOvoja
{
    public bool $protiZraku = true;

    public VrstaTal $tla;
    public BarvaElementaOvoja $barva;

    // lahko se overrida določilo iz TSG
    public ?bool $dobitekSS;
    public ?bool $adiabatno;

    // velja za konstrukcije v stiku z zemljino
    public ?float $obseg;
    public ?float $debelinaStene;
    public ?float $obodniPsi;
    public ?float $Lpi;
    public ?float $Lpe;
    public ?\stdClass $dodatnaIzolacija;

    // velja za vert. konstrukcije v stiku z zemljino
    public ?float $globina;
    public ?float $U_tla;

    // velja za tla proti neogrevani kleti
    public ?float $U_zid;
    public ?float $U_zid_nadTerenom;
    public ?float $visinaNadTerenom;
    public ?float $prostorninaKleti;
    public ?float $izmenjavaZraka;

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

        $EvalMath = new EvalMath();

        if (isset($config->protiZraku)) {
            $this->protiZraku = (bool)$config->protiZraku;
        } elseif (isset($this->konstrukcija->TSG->tip)) {
            $this->protiZraku = $this->konstrukcija->TSG->tip == 'zunanja';
        }
        $this->tla = VrstaTal::from($config->tla ?? 'pesek');

        $this->barva = BarvaElementaOvoja::from($config->barva ?? 'brez');

        if (isset($config->dobitekSS)) {
            $this->dobitekSS = $config->dobitekSS;
        }

        if (isset($config->adiabatno)) {
            $this->adiabatno = $config->adiabatno;
        }

        if (isset($config->idKonstrukcije)) {
            $this->idKonstrukcije = $config->idKonstrukcije;
        }

        if (isset($config->b)) {
            $this->b = $config->b;
        }

        $dobitekSS = !empty($this->konstrukcija->TSG->dobitekSS);
        if (isset($this->dobitekSS)) {
            $dobitekSS = $this->dobitekSS;
        }
        if (empty($config->barva) && $dobitekSS) {
            Log::warn(sprintf('Netransparenten element ovoja "%s" nima določene barve.', $this->idKonstrukcije));
        }

        if (!empty($config->obseg)) {
            $obseg = $config->obseg;
            if (gettype($obseg) == 'string') {
                $obseg = (float)$EvalMath->e($obseg);
            }
            $this->obseg = $obseg;
        }

        if (!empty($config->debelinaStene)) {
            $this->debelinaStene = gettype($config->debelinaStene) == 'string' ?
                (float)$EvalMath->e($config->debelinaStene) :
                $config->debelinaStene;
        }

        if (!empty($config->globina)) {
            $this->globina = gettype($config->globina) == 'string' ?
                (float)$EvalMath->e($config->globina) :
                $config->globina;
        }

        if (!empty($config->obodniPsi)) {
            $this->obodniPsi = $config->obodniPsi;
        }

        if (!empty($config->U_tla)) {
            $this->U_tla = $config->U_tla;
        }

        if (!empty($config->dodatnaIzolacija)) {
            $this->dodatnaIzolacija = $config->dodatnaIzolacija;
        }

        if (!empty($config->U_zid)) {
            $this->U_zid = $config->U_zid;
        }
        if (!empty($config->U_zid_nadTerenom)) {
            $this->U_zid_nadTerenom = $config->U_zid_nadTerenom;
        }
        if (!empty($config->visinaNadTerenom)) {
            $this->visinaNadTerenom = gettype($config->visinaNadTerenom) == 'string' ?
                (float)$EvalMath->e($config->visinaNadTerenom) :
                $config->visinaNadTerenom;
        }
        if (!empty($config->prostorninaKleti)) {
            $this->prostorninaKleti = gettype($config->prostorninaKleti) == 'string' ?
                (float)$EvalMath->e($config->prostorninaKleti) :
                $config->prostorninaKleti;
        }
        if (!empty($config->izmenjavaZraka)) {
            $this->izmenjavaZraka = gettype($config->izmenjavaZraka) == 'string' ?
                (float)$EvalMath->e($config->izmenjavaZraka) :
                $config->izmenjavaZraka;
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
        if (empty($this->konstrukcija) || (count(get_object_vars($this->konstrukcija)) == 0)) {
            throw new \Exception(sprintf(
                'Elementa ovoja z id="%1$s" je vezan na neobstoječo konstrukcijo "%2$s".',
                $this->id,
                $this->idKonstrukcije
            ));
        }
        if (empty($this->konstrukcija->TSG)) {
            throw new \Exception(sprintf('Konstrukcija elementa ovoja z id="%s" nima TSG podatkov.', $this->id));
        }

        if (!empty($this->konstrukcija->ogrRazvodT)) {
            // temperaturni korekcijski faktor
            // prilagoditev zaradi ploskovnega ogrevanja
            $this->b = ($this->konstrukcija->ogrRazvodT - $okolje->projektnaZunanjaT) /
                ($cona->notranjaTOgrevanje - $okolje->projektnaZunanjaT);
        }

        $dobitekSS = !empty($this->konstrukcija->TSG->dobitekSS);
        if (isset($this->dobitekSS)) {
            $dobitekSS = $this->dobitekSS;
        }

        // napolni podatke o vplivu zemljine
        if ($this->konstrukcija->TSG->tip != 'zunanja') {
            $this->vplivZemljine();
        } else {
            // faktor sončnega sevanja
            if ($dobitekSS) {
                foreach ($okolje->obsevanje as $line) {
                    if ($line->orientacija == $this->orientacija && $line->naklon == $this->naklon) {
                        $this->soncnoObsevanje = $line->obsevanje;
                        break;
                    }
                }
                if (empty($this->soncnoObsevanje)) {
                    throw new \Exception(sprintf(
                        'Sončno obsevanje za element %1$s z orientacijo "%2$s" ne obstaja',
                        $this->id ?? '',
                        $this->orientacija . ':' . $this->naklon
                    ));
                }
            }

            $this->H_ogrevanje = ($this->U + $cona->deltaPsi) * $this->povrsina * $this->b * $this->stevilo;
            $this->H_hlajenje = ($this->U + $cona->deltaPsi) * $this->povrsina * $this->b * $this->stevilo;
        }

        // izračun dodatnih temperatura
        $temperature = $this->osnovniTemperaturniPodatki($cona, $okolje);

        // transmisijske toplotne izgube za ogrevanje in hlajenje Qtr,m (kWh/m)
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = Calc::steviloDni($mesec);
            if ($this->konstrukcija->TSG->tip == 'zunanja') {
                // konstrukcija proti zraku
                $adiabatno = !empty($this->konstrukcija->TSG->adiabatno);
                if (isset($this->adiabatno)) {
                    $adiabatno = $this->adiabatno;
                }
                if ($adiabatno) {
                    $this->transIzgubeOgrevanje[$mesec] = 0;
                    $this->transIzgubeHlajenje[$mesec] = 0;
                    $this->solarniDobitkiOgrevanje[$mesec] = 0;
                    $this->solarniDobitkiHlajenje[$mesec] = 0;
                    continue;
                }
                $this->transIzgubeOgrevanje[$mesec] = $this->H_ogrevanje * 24 / 1000 *
                    $cona->deltaTOgrevanje[$mesec] * $stDni;

                $this->transIzgubeHlajenje[$mesec] = $this->H_hlajenje * 24 / 1000 *
                    $cona->deltaTHlajenje[$mesec] * $stDni;

                if ($dobitekSS) {
                    // svetla barva 0.3, srednja barva 0.6, temna barva 0.9
                    $alphaSr = !empty($this->options['referencnaStavba']) ? 0.5 : $this->barva->koeficientAlphaSr();
                    $Fsky = $this->naklon < 45 ? 1 : 0.5;
                    $hri = 4.14;
                    $dTsky = 11;

                    // toplotni tok zaradi osončenja
                    // ISO 52016-1:2017 enačba 124 na strani 105
                    $Qsol = $alphaSr * $this->konstrukcija->Rse * ($this->U + $cona->deltaPsi) * $this->povrsina *
                    $this->faktorSencenja[$mesec] * $this->soncnoObsevanje[$mesec] * $stDni;

                    // sevanje elementa proti nebu za trenutni mesec
                    // popravek po verziji v160:
                    // $Qsky = 0.001 * $Fsky * $this->konstrukcija->Rse * ($this->U + $cona->deltaPsi) *
                    $Qsky = $Fsky * $this->konstrukcija->Rse * ($this->U + $cona->deltaPsi) *
                        $this->povrsina * $hri * $dTsky * $stDni * 24;

                    $this->solarniDobitkiOgrevanje[$mesec] = ($Qsol - $Qsky) / 1000 * $this->stevilo;
                    $this->solarniDobitkiHlajenje[$mesec] = ($Qsol - $Qsky) / 1000 * $this->stevilo;
                } else {
                    $this->solarniDobitkiOgrevanje[$mesec] = 0;
                    $this->solarniDobitkiHlajenje[$mesec] = 0;
                }
            } else {
                // konstrukcija proti zemljini

                // toplotni tok proti tlom
                $this->izracunTokaProtiZemljini($mesec, $okolje, $temperature);

                $this->solarniDobitkiOgrevanje[$mesec] = 0;
                $this->solarniDobitkiHlajenje[$mesec] = 0;
            }
        }

        if ($this->konstrukcija->TSG->tip != 'zunanja') {
            $this->H_ogrevanje = $this->H_ogrevanje / $temperature['stMesecevOgrevanja'] /
                ($temperature['povprecnaTOgrevanja'] - $okolje->povprecnaLetnaTemp);

            $this->H_hlajenje = $this->H_hlajenje / (12 - $temperature['stMesecevOgrevanja']) /
                ($temperature['povprecnaTHlajenja'] - $okolje->povprecnaLetnaTemp);
        }
    }

    /**
     * Doda podatke o zemljini na konstrukcijo
     *
     * @return void
     */
    private function vplivZemljine()
    {
        $params = (object)[
            'id' => $this->id,
            'tla' => $this->tla->value,
            'povrsina' => $this->povrsina,
            'obseg' => $this->obseg ?? null,
            'debelinaStene' => $this->debelinaStene ?? null,
            'globina' => $this->globina ?? null,
            'dodatnaIzolacija' => $this->dodatnaIzolacija ?? null,
            'U_tla' => $this->U_tla ?? null,
            'U_zid' => $this->U_zid ?? null,
            'U_zid_nadTerenom' => $this->U_zid_nadTerenom ?? null,
            'visinaNadTerenom' => $this->visinaNadTerenom ?? null,
            'prostorninaKleti' => $this->prostorninaKleti ?? null,
            'izmenjavaZraka' => $this->izmenjavaZraka ?? null,
        ];

        $result = \App\Lib\CalcKonstrukcije::izracunajVplivZemljine(
            $this->konstrukcija->TSG->tip ?? '',
            $this->konstrukcija->U,
            $params
        );

        if ($result !== null) {
            $this->U = $result->U_earth;
            if (isset($result->obodniPsi)) {
                $this->obodniPsi = $result->obodniPsi;
            }
            $this->Lpi = $result->Lpi;
            $this->Lpe = $result->Lpe;
        }
    }

    /**
     * Izračun dodatnih temperaturnih podatkov
     *
     * @param \App\Calc\GF\Cone\Cona $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @return array
     */
    private function osnovniTemperaturniPodatki($cona, $okolje)
    {
        $notranjaT = [];
        $zunanjaT = [];

        $povprecnaTOgrevanja = 0;
        $povprecnaTHlajenja = 0;
        $stMesecevOgrevanja = 0;
        foreach (array_keys(Calc::MESECI) as $mesec) {
            // določi temperaturo prostora
            if (Calc::jeMesecBrezOgrevanja($mesec)) {
                $notranjaT[$mesec] = $cona->notranjaTHlajenje;
                $povprecnaTHlajenja += $notranjaT[$mesec];
            } else {
                if (empty($this->konstrukcija->TSG->talnoGretje)) {
                    $notranjaT[$mesec] = $cona->notranjaTOgrevanje;
                } else {
                    $notranjaT[$mesec] = $okolje->zunanjaT[$mesec] +
                        $this->b * ($cona->notranjaTOgrevanje - $okolje->zunanjaT[$mesec]);
                }
                $povprecnaTOgrevanja += $notranjaT[$mesec];
                $stMesecevOgrevanja++;
            }
        }

        $povprecnaTHlajenja = $povprecnaTHlajenja / (12 - $stMesecevOgrevanja);
        $povprecnaTOgrevanja = $povprecnaTOgrevanja / $stMesecevOgrevanja;
        $povprecnaNotranjaT = ($povprecnaTOgrevanja + $povprecnaTHlajenja) / 2;

        return ['notranjaT' => $notranjaT, 'zunanjaT' => $zunanjaT, 'povprecnaTHlajenja' => $povprecnaTHlajenja,
            'povprecnaTOgrevanja' => $povprecnaTOgrevanja, 'povprecnaNotranjaT' => $povprecnaNotranjaT,
            'stMesecevOgrevanja' => $stMesecevOgrevanja,
        ];
    }

    /**
     * Izračun toka proti zemljini
     *
     * @param int $mesec Št. meseca
     * @param \stdClass $okolje Podatki okolja
     * @param array $temperature Dodatne temperature iz osnovniTemperaturniPodatki()
     * @return void
     */
    private function izracunTokaProtiZemljini($mesec, $okolje, $temperature)
    {
        extract($temperature);
        $povprecnaNotranjaT = $povprecnaNotranjaT ?? 20;
        $notranjaT = $notranjaT ?? 20;

        $stDni = Calc::steviloDni($mesec);

        $urniToplotniTok = $this->U *
            $this->povrsina * ($povprecnaNotranjaT - $okolje->povprecnaLetnaTemp) +
            $this->obseg * ($this->obodniPsi ?? 0) * ($notranjaT[$mesec] - $okolje->zunanjaT[$mesec]) -
            $this->Lpi * ($povprecnaNotranjaT - $notranjaT[$mesec]) +
            $this->Lpe * ($okolje->povprecnaLetnaTemp - $okolje->zunanjaT[$mesec]);

        $this->transIzgubeOgrevanje[$mesec] = $urniToplotniTok * 24 * $stDni / 1000 * $this->stevilo;
        $this->transIzgubeHlajenje[$mesec] = $urniToplotniTok * 24 * $stDni / 1000 * $this->stevilo;

        if (Calc::jeMesecBrezOgrevanja($mesec)) {
            $this->H_hlajenje += $urniToplotniTok * $this->stevilo;
        } else {
            $this->H_ogrevanje += $urniToplotniTok * $this->stevilo;
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

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            // celotne konstrukcije ne shranjujemo (glej ElementOvoja::export() in Cona::hydrateKonstrukcije())
            if ($prop->getName() == 'konstrukcija') {
                continue;
            }
            if ($prop->isInitialized($this)) {
                $elementOvoja->{$prop->getName()} = $prop->getValue($this);

                // pretvori enum v string
                if ($elementOvoja->{$prop->getName()} instanceof \UnitEnum) {
                    /* @phpstan-ignore-next-line */
                    $elementOvoja->{$prop->getName()} = $elementOvoja->{$prop->getName()}->value;
                }
            }
        }

        return $elementOvoja;
    }
}
