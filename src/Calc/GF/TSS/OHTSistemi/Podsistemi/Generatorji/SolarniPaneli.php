<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Izbire\VrstaSSE;
use App\Calc\GF\TSS\TSSPorociloNiz;
use App\Calc\GF\TSS\TSSPorociloPodatek;
use App\Lib\Calc;

class SolarniPaneli extends Generator
{
    public VrstaSSE $tip;

    public float $povrsina;
    public string $orientacija;
    public int $naklon;

    public array $X;
    public array $Y;
    public array $f_sol;
    public array $proizvedenaEnergijaSSE;

    public ?array $soncnoObsevanje;
    public float $skupnoSoncnoObsevanje;

    /**
     * Class Constructor
     *
     * @param \stdClass $config Configuration
     * @return void
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
    }

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        parent::parseConfig($config);

        $this->tip = VrstaSSE::from($config->tip ?? 'ploscati');
        $this->povrsina = (float)($config->povrsina ?? 0.0);
        $this->naklon = $config->naklon ?? 45;
        $this->orientacija = $config->orientacija ?? 'J';
    }

    /**
     * Analiza podsistema
     *
     * @param array $toplotneIzgube Potrebna energija predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function analiza($toplotneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $this->izracunSoncnegaObsevanja($okolje);
        parent::analiza($toplotneIzgube, $sistem, $cona, $okolje, $params);
    }

    /**
     * Izračun potrebne energije generatorja
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function toplotneIzgube($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        switch ($namen) {
            case 'ogrevanje':
                $this->toplotneIzgubeOgrevanje($vneseneIzgube, $sistem, $cona, $okolje, $params);
                break;
            case 'tsv':
                $this->toplotneIzgubeTSV($vneseneIzgube, $sistem, $cona, $okolje, $params);
                break;
        }
    }

    /**
     * Izračun potrebne energije generatorja v sistemu ST
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    private function toplotneIzgubeTSV($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        $U_sc = $this->tip->a1() + $this->tip->a2() * 40 + (5 + 0.5 * $this->povrsina) / $this->povrsina;

        $hranilnik = array_first($sistem->hranilniki);
        $volumenHranilnika = (float)array_reduce(
            $sistem->hranilniki,
            fn($sum, $hranilnik) => $sum += $hranilnik->volumen
        );
        if ($volumenHranilnika == 0.00) {
            throw new \Exception(sprintf('Za generator SolarniPaneli "%s" ne obstaja noben hranilnik.', $this->id));
        }

        $f_sto = pow(75 / ($volumenHranilnika * (1 - 0.5) / $this->povrsina), 0.25);

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $stDniDelovanja = $stDni;

            $this->vneseneIzgube['tsv'][$mesec] = $vneseneIzgube[$mesec];
            $this->toplotneIzgube['tsv'][$mesec] = 0;
            $this->vracljiveIzgube['tsv'][$mesec] = 0;
            $this->vracljiveIzgubeTSV['tsv'][$mesec] = 0;

            $potrebnaEnergija = $vneseneIzgube[$mesec] + $this->toplotneIzgube[$namen][$mesec];

            // še izračun solarnega dela
            $this->X[$mesec] = 0;
            $this->Y[$mesec] = 0;
            if ($this->povrsina * $potrebnaEnergija > 0) {
                $this->X[$mesec] = $this->povrsina * $U_sc * 0.9 *
                    ((11.6 + 1.18 * 40 + 3.86 * 10 - 1.32 * $okolje->zunanjaT[$mesec]) - $okolje->zunanjaT[$mesec]) *
                    $f_sto * 24 * $stDniDelovanja / ($potrebnaEnergija * 1000);
                $this->Y[$mesec] = $this->povrsina * $this->tip->IAM() * $this->tip->eta0() * 0.9 *
                    $this->soncnoObsevanje[$mesec] / 24 * $stDniDelovanja * 24 / ($potrebnaEnergija * 1000);

                // energija ki jo pridelajo paneli
                // $Q_w_out_sol
                $this->proizvedenaEnergijaSSE[$mesec] = (1.029 * $this->Y[$mesec] - 0.065 * $this->X[$mesec] -
                    0.245 * pow($this->Y[$mesec], 2) + 0.0018 * pow($this->X[$mesec], 2) +
                    0.0215 * pow($this->Y[$mesec], 3)) *
                    $potrebnaEnergija * 0.98;

                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // delež potrebne energije, ki se jo pokriva s SSE
                $f_sol_nekorig = $stDniDelovanja == 0 ? 0 :
                    ($this->proizvedenaEnergijaSSE[$mesec] + $sistem->tsv->vracljiveIzgubeVTSV[$mesec]) /
                    $potrebnaEnergija;

                $this->f_sol[$mesec] = $f_sol_nekorig < 0 ? 0.0 : ($f_sol_nekorig > 1 ? 1.0 : $f_sol_nekorig);

                $Q_w_bu_sol = (1 - $this->f_sol[$mesec]) * $potrebnaEnergija +
                    (1 - $this->f_sol[$mesec]) * 0.2 * $hranilnik->toplotneIzgube[$namen][$mesec];

                $this->nepokritaEnergija[$namen][$mesec] = $Q_w_bu_sol;
            }
        }
    }

    /**
     * Izračun potrebne energije generatorja v sistemu ogrevanja
     *
     * @param array $vneseneIzgube Vnešene izgube predhodnih TSS
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    private function toplotneIzgubeOgrevanje($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->vneseneIzgube['ogrevanje'][$mesec] = 0;
            $this->toplotneIzgube['ogrevanje'][$mesec] = 0;
            $this->vracljiveIzgube['ogrevanje'][$mesec] = 0;
        }
    }

    /**
     * Izračun potrebne električne energije
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function potrebnaElektricnaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        if ($namen == 'tsv') {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $this->potrebnaElektricnaEnergija['tsv'][$mesec] =
                    ($this->potrebnaElektricnaEnergija['tsv'][$mesec] ?? 0) + 0;

                $this->vracljiveIzgubeAux[$mesec] = 0;
            }
        }
        if ($namen == 'ogrevanje') {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $this->potrebnaElektricnaEnergija['ogrevanje'][$mesec] =
                    ($this->potrebnaElektricnaEnergija['ogrevanje'][$mesec] ?? 0) + 0;

                $this->vracljiveIzgubeAux[$mesec] = 0;
            }
        }
    }

    /**
     * Uporabljena obnovljiva energija iz okolja
     *
     * @param array $vneseneIzgube Vnesene izgube
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function obnovljivaEnergija($vneseneIzgube, $sistem, $cona, $okolje, $params = [])
    {
        $namen = $params['namen'];

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->obnovljivaEnergija[$namen][$mesec] = 0;
        }
    }

    /**
     * Izračun sončnega obsevanja
     *
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public function izracunSoncnegaObsevanja($okolje)
    {
        // faktor sončnega sevanja
        foreach ($okolje->obsevanje as $line) {
            if ($line->orientacija == $this->orientacija && $line->naklon == $this->naklon) {
                $this->soncnoObsevanje = $line->obsevanje;
                break;
            }
        }
        if (empty($this->soncnoObsevanje)) {
            throw new \Exception(sprintf('Sončno obsevanje za SSE "%s" ne obstaja', $this->id));
        }

        $this->skupnoSoncnoObsevanje = 0;
        foreach ($this->soncnoObsevanje as $mesec => $obsevanje) {
            $this->skupnoSoncnoObsevanje += $obsevanje * cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
        }
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $this->porociloNizi[] = new TSSPorociloNiz(
            'X',
            '-',
            $this->X,
            1
        );
        $this->porociloNizi[] = new TSSPorociloNiz(
            'Y',
            '-',
            $this->Y,
            1
        );
        $this->porociloNizi[] = new TSSPorociloNiz(
            'f<sub>sol</sub>',
            'Faktor SSE',
            $this->f_sol,
            2
        );
        $this->porociloNizi[] = new TSSPorociloNiz(
            'Q<sub>W,sol,del</sub>',
            'Proizvedena energija SSE',
            $this->proizvedenaEnergijaSSE,
            1
        );

        $this->porociloPodatki[] = new TSSPorociloPodatek(
            '',
            'Tip SSE',
            $this->tip->name,
            ''
        );
        $this->porociloPodatki[] = new TSSPorociloPodatek(
            'A<sub>sc</sub>',
            'Efektivna površina SSE',
            $this->povrsina,
            'm2'
        );
        $this->porociloPodatki[] = new TSSPorociloPodatek(
            '',
            'Orientacija',
            $this->orientacija,
            ''
        );
        $this->porociloPodatki[] = new TSSPorociloPodatek(
            '',
            'Naklon',
            $this->naklon,
            '°'
        );

        $sistem = parent::export();

        return $sistem;
    }
}
