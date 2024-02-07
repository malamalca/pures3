<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\FotonapetostniSistemi;

use App\Calc\GF\TSS\FotonapetostniSistemi\Izbire\VrstaSoncnihCelic;
use App\Calc\GF\TSS\FotonapetostniSistemi\Izbire\VrstaVgradnje;
use App\Calc\GF\TSS\TSSVrstaEnergenta;
use App\Lib\Calc;

class FotonapetostniSistem
{
    public string $id;
    public string $idCone;
    public string $tss = 'fotovoltaika';

    public float $povrsina;
    public string $orientacija;
    public int $naklon;
    public bool $sencenje;
    public VrstaSoncnihCelic $vrsta;
    public VrstaVgradnje $vgradnja;

    public float $kontrolniFaktor;
    public float $koeficientMoci;
    public float $koeficientVgradnje;

    public bool $vplivUjemanja = false;

    public float $nazivnaMoc;

    public array $porabljenaEnergija = [];
    public array $oddanaElektricnaEnergija = [];
    public array $potrebnaEnergija = [];
    public array $proizvedenaElektricnaEnergija = [];
    public array $faktorUjemanja = [];

    public array $energijaPoEnergentih = [];
    public array $proizvedenaEnergijaPoEnergentih = [];
    public array $oddanaEnergijaPoEnergentih = [];

    /**
     * Class Constructor
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function __construct($config = null)
    {
        if ($config) {
            $this->parseConfig($config);
        }
    }

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    public function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->id = $config->id;
        $this->idCone = $config->idCone;
        $this->povrsina = $config->povrsina;
        $this->orientacija = $config->orientacija;
        $this->naklon = $config->naklon;

        $this->sencenje = (bool)$config->sencenje;

        $this->kontrolniFaktor = $config->kontrolniFaktor;

        $this->vrsta = VrstaSoncnihCelic::from($config->vrsta);
        $this->koeficientMoci = $this->vrsta->koeficientMoci();
        $this->vgradnja = VrstaVgradnje::from($config->vgradnja);
        $this->koeficientVgradnje = $this->vgradnja->koeficientVgradnje();
        $this->nazivnaMoc = $this->povrsina * $this->koeficientMoci;

        $this->vplivUjemanja = (bool)($config->vplivUjemanja ?? false);
    }

    /**
     * Analiza podsistema
     *
     * @param array $potrebnaEnergija Potrebna energija predhodnih TSS
     * @param \stdClass $cona Podatki cone
     * @param \stdClass $okolje Podatki okolja
     * @param array $params Dodatni parametri za izračun
     * @return void
     */
    public function analiza($potrebnaEnergija, $cona, $okolje, $params = [])
    {
        $this->potrebnaEnergija = $potrebnaEnergija;

        $proizvedenaElektricnaEnergija = [];
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            // faktor sončnega sevanja
            $solarnoObsevanje = null;
            foreach ($okolje->obsevanje as $line) {
                if ($line->orientacija == $this->orientacija && $line->naklon == $this->naklon) {
                    $solarnoObsevanje = $line->obsevanje;
                    break;
                }
            }
            if (!$solarnoObsevanje) {
                throw new \Exception('Podatki za solarno obsevanja za fotonapetosni sistem ne obstajajo.');
            }
            $solarnoObsevanje = $stDni * $solarnoObsevanje[$mesec] / 1000;

            $this->proizvedenaElektricnaEnergija[$mesec] = $this->povrsina * $solarnoObsevanje *
                $this->vrsta->koeficientMoci() * $this->vgradnja->koeficientVgradnje();

            if ($this->vplivUjemanja) {
                $this->faktorUjemanja[$mesec] = (
                    $this->proizvedenaElektricnaEnergija[$mesec] / $this->potrebnaEnergija[$mesec] +
                    $this->potrebnaEnergija[$mesec] / $this->proizvedenaElektricnaEnergija[$mesec] - 1
                ) / (
                    $this->proizvedenaElektricnaEnergija[$mesec] / $this->potrebnaEnergija[$mesec] +
                    $this->potrebnaEnergija[$mesec] / $this->proizvedenaElektricnaEnergija[$mesec]
                );
            } else {
                $this->faktorUjemanja[$mesec] = 1;
            }

            $this->porabljenaEnergija[$mesec] = $this->faktorUjemanja[$mesec] *
                min($this->proizvedenaElektricnaEnergija[$mesec], $potrebnaEnergija[$mesec]);

            if ($this->vplivUjemanja) {
                $this->oddanaElektricnaEnergija[$mesec] = 0;
            } else {
                $this->oddanaElektricnaEnergija[$mesec] = $this->kontrolniFaktor *
                    ($this->proizvedenaElektricnaEnergija[$mesec] - $this->porabljenaEnergija[$mesec]);
            }
        }

        $this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] = -array_sum($this->porabljenaEnergija);
        $this->proizvedenaEnergijaPoEnergentih[TSSVrstaEnergenta::Sonce->value] =
            array_sum($this->oddanaElektricnaEnergija) + array_sum($this->porabljenaEnergija);
        $this->oddanaEnergijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] =
            array_sum($this->oddanaElektricnaEnergija);
    }
}
