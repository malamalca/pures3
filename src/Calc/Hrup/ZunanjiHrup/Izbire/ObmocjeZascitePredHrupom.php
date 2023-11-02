<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZunanjiHrup\Izbire;

enum ObmocjeZascitePredHrupom: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case I = 'I';
    case II = 'II';
    case III = 'III';
    case IV = 'IV';

    /**
     * Vrne kazalce za predmetno območje
     *
     * @param string $kazalec Ime kazalca območja
     * @return int
     */
    public function kazalci($kazalec)
    {
        $indexKazalca = $kazalec == 'Lnoc' ? 1 : 0;
        $kazalci = [[50, 40], [55, 45], [60, 50], [75, 65]];

        return $kazalci[$this->getOrdinal()][$indexKazalca];
    }
}
