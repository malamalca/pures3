<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\Izbire;

enum VrstaLegeStavbe: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case NaPodezelju = 'naPodezelju';
    case VisokaStavbaVMestu = 'visokaStavbaVMestu';
    case NaPodezeljuMedDrevesi = 'naPodezeljuMedDrevesi';
    case ObkrozenaStavbaVMestu = 'obkrozenaStavbaVMestu';
    case StavbaVPredmestju = 'stavbaVPredmestju';
    case PovprecnaStavbaVMestu = 'povprecnaStavbaVMestu';

    /**
     * Vrne koeficient vpliva vetra po tabeli 8.8
     *
     * @param \App\Calc\GF\Cone\Izbire\VrstaIzpostavljenostiFasad $zavetrovanje Zavetrovanje
     * @return float
     */
    public function koeficientVplivaVetra(VrstaIzpostavljenostiFasad $zavetrovanje)
    {
        $k = [
            VrstaIzpostavljenostiFasad::EnaFasada->getOrdinal() => [0.03, 0.03, 0.02, 0.02, 0.01, 0.01],
            VrstaIzpostavljenostiFasad::VecFasad->getOrdinal() => [0.1, 0.1, 0.07, 0.07, 0.04, 0.04],
        ];

        return $k[$zavetrovanje->getOrdinal()][$this->getOrdinal()];
    }

    /**
     * Vrne sifro za EI XML
     *
     * @return int
     */
    public function sifraEI()
    {
        $sifre = [1, 1, 2, 2, 3, 3];

        return $sifre[$this->getOrdinal()];
    }
}
