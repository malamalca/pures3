<?php
declare(strict_types=1);

namespace App\Calc\TSS;

enum TSSVrstaEnergenta: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Okolje = 'okolje';
    case Elektrika = 'elektrika';
    case Biomasa = 'biomasa';
    case ELKO = 'ELKO';

    /**
     * Vrne utezni faktor za vrsto energenta
     *
     * @param string $faktor Kateri faktrot
     * @return float
     */
    public function utezniFaktor($faktor)
    {
        $utezniFaktorjiF_Ptot = [1, 2.5];
        $utezniFaktorjiF_Pnren = [0, 1.5];
        $utezniFaktorjiF_Pren = [1, 1];

        switch ($faktor) {
            case 'tot':
                return $utezniFaktorjiF_Ptot[$this->getOrdinal()];
            case 'nren':
                return $utezniFaktorjiF_Pnren[$this->getOrdinal()];
            case 'ren':
                return $utezniFaktorjiF_Pren[$this->getOrdinal()];
            default:
                throw new \Exception('Utezni faktor ne obstaja');
        }
    }

    /**
     * Vrne faktor izpustov CO2
     *
     * @return float
     */
    public function faktorIzpustaCO2()
    {
        $faktorjiIzpusta = [0, 0.42];

        return $faktorjiIzpusta[$this->getOrdinal()];
    }

    /**
     * Minimalni izkoristek sistema OgrHlaTsv
     *
     * @return float
     */
    public function minimalniIzkoristekOgrHlaTsv(): float
    {
        return match ($this) {
            TSSVrstaEnergenta::Elektrika => 0.5,
            TSSVrstaEnergenta::Biomasa => 0.65,
            // vsi ostali energenti
            TSSVrstaEnergenta::Okolje,
            TSSVrstaEnergenta::ELKO => 0.7,
        };
    }

    /**
     * Vrne naziv energenta
     *
     * @return string
     */
    public function naziv()
    {
        $naziviEnergentov = ['Energija okolja', 'Električna energija'];

        return $naziviEnergentov[$this->getOrdinal()];
    }
}
