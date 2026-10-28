<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Izbire;

use App\Calc\GF\TSS\OHTSistemi\ToplovodniOHTSistem;
use App\Calc\GF\TSS\TSSVrstaEnergenta;

enum VrstaRezima: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Rezim_35_30 = '35/30';
    case Rezim_40_30 = '40/30';
    case Rezim_55_45 = '55/45';
    case Rezim_70_55 = '70/55';

    /**
     * θh,n,d – standardna (projektna) temperatura razvodnega podsistema. Vrednosti so podane v Tabeli 11
     *
     * @return float
     */
    public function projektnaTemperatura()
    {
        // 70/55: srednja temperatura ogrevnega medija (70 + 55) / 2 = 62,5 °C, enako kot pri 55/45 (50 °C)
        $temperature = [35, 35, 50, 62.5];

        return $temperature[$this->getOrdinal()];
    }

    /**
     * Vrne srednjo temperaturo režima
     *
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @return float
     */
    public function srednjaTemperatura($sistem)
    {
        if (
            $sistem instanceof ToplovodniOHTSistem ||
            $sistem->energent == TSSVrstaEnergenta::Elektrika
        ) {
            // za ploskovno | toplovodno+elektrika; 70/55: (70 + 55) / 2 = 62,5 °C
            $srednjeTemperature = [32.5, 35, 50, 62.5];
        } else {
            // vse ostalo
            $this->preveriPodporoRezima70_55('izven toplovodnih sistemov');
            $srednjeTemperature = [35, 50, 62.5];
        }

        return $srednjeTemperature[$this->getOrdinal()];
    }

    /**
     * Vrne temperaturo ponora
     *
     * @return float
     */
    public function temperaturaPonora()
    {
        $this->preveriPodporoRezima70_55('pri toplotnih črpalkah (tabela relativnih moči velja za temperature ponora 35, 40 in 55 °C)');
        $temperaturePonora = [35, 40, 55];

        return $temperaturePonora[$this->getOrdinal()];
    }

    /**
     * Vrne faktor COP za toplotne črpalke glede na režim
     *
     * @return float
     */
    public function faktorDeltaTempTC()
    {
        $this->preveriPodporoRezima70_55('pri toplotnih črpalkah');

        return $this->getOrdinal() == 0 ? 1.02 : 1.051;
    }

    /**
     * ΔθHK – temperaturna razlika pri standardnem temperaturnem režimu ogrevalnega sistema [°C]
     *
     * @param \App\Calc\GF\TSS\OHTSistemi\OHTSistem $sistem Podatki sistema
     * @return int
     */
    public function temperaturnaRazlikaStandardnegaRezima($sistem)
    {
        // 70/55: ΔθHK = 70 - 55 = 15 °C (toplovodni sistemi)
        $deltaTHKLookup = [[5, 10], [10, 15], [10, 15], [15, null]];

        if (
            $sistem instanceof ToplovodniOHTSistem ||
            $sistem->energent == TSSVrstaEnergenta::Elektrika
        ) {
            $deltaT_HK = $deltaTHKLookup[$this->getOrdinal()][0];
        } else {
            $this->preveriPodporoRezima70_55('izven toplovodnih sistemov');
            $deltaT_HK = $deltaTHKLookup[$this->getOrdinal()][1];
        }

        return $deltaT_HK;
    }

    /**
     * Režim 70/55 je podprt samo pri toplovodnih sistemih z ogrevali (radiatorji, konvektorji). Pri drugih
     * sistemih in pri toplotnih črpalkah ni tabeliranih vrednosti, zato izračun ustavimo z jasnim sporočilom.
     *
     * @param string $kje Opis okoliščin za sporočilo o napaki
     * @return void
     * @throws \Exception Če je režim 70/55 in okoliščine niso podprte
     */
    private function preveriPodporoRezima70_55(string $kje): void
    {
        if ($this == self::Rezim_70_55) {
            throw new \Exception(sprintf('Temperaturni režim 70/55 ni podprt %s.', $kje));
        }
    }
}
