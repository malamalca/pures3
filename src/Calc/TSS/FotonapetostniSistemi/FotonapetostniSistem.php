<?php
declare(strict_types=1);

namespace App\Calc\TSS\FotonapetostniSistemi;

use App\Calc\TSS\FotonapetostniSistemi\Izbire\VrstaSoncnihCelic;
use App\Calc\TSS\FotonapetostniSistemi\Izbire\VrstaVgradnje;
use App\Calc\TSS\TSSVrstaEnergenta;
use App\Lib\Calc;

class FotonapetostniSistem
{
    public string $id;

    public float $povrsina;
    public string $orientacija;
    public int $naklon;
    public bool $sencenje;
    public VrstaSoncnihCelic $vrsta;
    public VrstaVgradnje $vgradnja;

    public float $kontrolniFaktor;

    public array $energijaPoEnergentih = [];
    public array $porabljenaEnergija = [];
    public array $oddanaElektricnaEnergija = [];

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
        $this->povrsina = $config->povrsina;
        $this->orientacija = $config->orientacija;
        $this->naklon = $config->naklon;

        $this->sencenje = (bool)$config->sencenje;

        $this->kontrolniFaktor = $config->kontrolniFaktor;

        $this->vrsta = VrstaSoncnihCelic::from($config->vrsta);
        $this->vgradnja = VrstaVgradnje::from($config->vgradnja);
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
        $celotnaEnergijaObsevanja = [];
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
            $solarnoObsevanje = $stDni * $solarnoObsevanje[$mesec] / 1000;

            $celotnaEnergijaObsevanja[$mesec] = $this->povrsina * $solarnoObsevanje *
                $this->vrsta->koeficientMoci() * $this->vgradnja->koeficientVgradnje();

            $this->porabljenaEnergija[$mesec] = $this->kontrolniFaktor *
                min($celotnaEnergijaObsevanja[$mesec], $potrebnaEnergija[$mesec]);

            $this->oddanaElektricnaEnergija[$mesec] = $this->kontrolniFaktor *
                ($celotnaEnergijaObsevanja[$mesec] - $this->porabljenaEnergija[$mesec]);
        }

        $this->energijaPoEnergentih[TSSVrstaEnergenta::Elektrika->value] = -array_sum($this->porabljenaEnergija);
        $this->energijaPoEnergentih[TSSVrstaEnergenta::Okolje->value] =
            array_sum($this->oddanaElektricnaEnergija) + array_sum($this->porabljenaEnergija);
    }
}
