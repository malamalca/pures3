<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OHTSistemi\Podsistemi;

use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\ElektricniGrelnik;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Kotel;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\SplitSistemKlima;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\ToplotnaCrpalkaZrakVoda;
use App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\ToplotnaPodpostaja;

class GeneratorFactory
{
    /**
     * Ustvari ustrezen generator glede na podan tip
     *
     * @param string $type Tip razvoda
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\GF\TSS\OHTSistemi\Podsistemi\Generatorji\Generator
     */
    public static function create($type, $options = null)
    {
        switch ($type) {
            case 'TC_zrakvoda':
            case 'TC_zrakvodaTSV':
                return new ToplotnaCrpalkaZrakVoda($options);
            case 'toplotnaPodpostaja':
                return new ToplotnaPodpostaja($options);
            case 'plinskiKotel':
                return new Kotel('PlinskiKotel', $options);
            case 'biomasa':
                return new Kotel('Biomasa', $options);
            case 'elektricniGrelnik':
                return new ElektricniGrelnik($options);
            case 'splitHlajenje':
                return new SplitSistemKlima($options);
            default:
                throw new \Exception(sprintf('Generator : Vrsta "%s" ne obstaja', $type));
        }
    }
}
