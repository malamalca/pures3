<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZunanjiHrup\Izbire;

enum OblikaFasade: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Ravna = 'ravna';
    case KonzolaZgoraj = 'konzolaZgoraj';
    case Balkon = 'balkon';
    case BalkonZOgrajo = 'balkonZOgrajo';
    case BalkonZOgrajoInSteklom = 'balkonZOgrajoInSteklom';
    case LoggiaSKonzolo = 'loggiaSKonzolo';
    case LoggiaSKonzoloInOgrajo = 'loggiaSKonzoloInOgrajo';
    case Loggia = 'loggia';
    case Terasa = 'terasa';
    case TerasaZOgrajo = 'terasaZOgrajo';

    /**
     * Vrne koeficient glede na vrsto celic
     *
     * @param \App\Calc\Hrup\ZunanjiHrup\Izbire\KoeficientStropa|null $koeficientStropa Koeficient stropa
     * @param \App\Calc\Hrup\ZunanjiHrup\Izbire\VisinaLinijePogleda|null $visinaLinijePogleda Višina linije pogleda
     * @return float
     */
    public function faktorOblike(?KoeficientStropa $koeficientStropa, ?VisinaLinijePogleda $visinaLinijePogleda)
    {
        switch ($this) {
            case OblikaFasade::Ravna:
                return 0;
            case OblikaFasade::KonzolaZgoraj:
                $factors = [-1, -1, 0];

                return $factors[$koeficientStropa->getOrdinal()];
            case OblikaFasade::Balkon:
                $factors = [[-1, -1, 1], [-1, 0, 1], [0, 2, 2]];

                return $factors[$koeficientStropa->getOrdinal()][$visinaLinijePogleda->getOrdinal()];
            case OblikaFasade::BalkonZOgrajo:
                $factors = [[0, 0, 2], [0, 1, 2], [1, 3, 3]];

                return $factors[$koeficientStropa->getOrdinal()][$visinaLinijePogleda->getOrdinal()];
            case OblikaFasade::BalkonZOgrajoInSteklom:
                $factors = [3, 4, 6];

                return $factors[$koeficientStropa->getOrdinal()];
            case OblikaFasade::LoggiaSKonzolo:
                $factors = [[-1, -1, 1], [-1, 1, 2], [0, 3, 3]];

                return $factors[$koeficientStropa->getOrdinal()][$visinaLinijePogleda->getOrdinal()];
            case OblikaFasade::LoggiaSKonzoloInOgrajo:
                $factors = [[0, 0, 2], [0, 2, 3], [1, 4.5, 4]];

                return $factors[$koeficientStropa->getOrdinal()][$visinaLinijePogleda->getOrdinal()];
            case OblikaFasade::Loggia:
                $factors = [[1, 1, 1], [1, 1, 1], [2, 2, 2]];

                return $factors[$koeficientStropa->getOrdinal()][$visinaLinijePogleda->getOrdinal()];
            case OblikaFasade::Terasa:
                $factors = [[1, 3, 4], [1, 4, 4], [1, 5, 5]];

                return $factors[$koeficientStropa->getOrdinal()][$visinaLinijePogleda->getOrdinal()];
            case OblikaFasade::TerasaZOgrajo:
                $factors = [[3, 5, 6], [3, 6, 6], [3, 7, 7]];

                return $factors[$koeficientStropa->getOrdinal()][$visinaLinijePogleda->getOrdinal()];
        }

        return 0;
    }
}
