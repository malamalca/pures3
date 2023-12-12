<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi;

use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Kotel;
//use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\ToplotnaCrpalkaZemljaVoda;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\ToplotnaCrpalkaZrakVoda;

class GeneratorFactory
{
    /**
     * Ustvari ustrezen generator glede na podan tip
     *
     * @param string $type Tip razvoda
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Generator
     */
    public static function create($type, $options = null)
    {
        switch ($type) {
            case 'TC_zrakvoda':
            case 'TC_zrakvodaTSV':
                return new ToplotnaCrpalkaZrakVoda($options);
            //case 'TC_zemljavoda':
            //    return new ToplotnaCrpalkaZemljaVoda($options);
            case 'plinskiKotel':
                return new Kotel('PlinskiKotel', $options);
            case 'biomasa':
                return new Kotel('Biomasa', $options);
            default:
                throw new \Exception(sprintf('Generator : Vrsta "%s" ne obstaja', $type));
        }
    }
}
