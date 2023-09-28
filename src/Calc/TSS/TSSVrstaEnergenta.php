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
    case ZP = 'zemeljskiPlin';
    case Daljinsko = 'daljinsko';
    case UNP = 'UNP';

    /**
     * Vrne utezni faktor za vrsto energenta
     *
     * @param string $faktor Kateri faktrot
     * @return float
     */
    public function utezniFaktor($faktor)
    {
        $utezniFaktorjiF_Pnren = [0, 1.5, 0.2, 1.1, 1.1, 1.12, 1.1];
        $utezniFaktorjiF_Pren = [1, 1, 1, 0, 0, 0.06, 0];
        $utezniFaktorjiF_Ptot = [1, 2.5, 1.2, 1.1, 1.1, 1.18, 1.1];

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
        $faktorjiIzpusta = [0, 0.42, 0.04, 0.29, 0.22, 0.396, 0,22];

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
            TSSVrstaEnergenta::UNP,
            TSSVrstaEnergenta::ZP,
            TSSVrstaEnergenta::Daljinsko,
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
        $naziviEnergentov = [
            'Energija okolja',
            'Električna energija',
            'Biomasa',
            'ELKO',
            'Zemeljski plin',
            'Daljinsko ogrevanje',
            'UNP',
        ];

        return $naziviEnergentov[$this->getOrdinal()];
    }
}
