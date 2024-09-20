<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi;

use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\ElektricniGrelnik;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Kotel;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\ToplotnaCrpalkaZrakVoda;
use App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\ToplotnaPodpostaja;

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
            case 'toplotnaPodpostaja':
                return new ToplotnaPodpostaja($options);
            case 'plinskiKotel':
                return new Kotel('PlinskiKotel', $options);
            case 'biomasa':
                return new Kotel('Biomasa', $options);
            case 'elektricniGrelnik':
                return new ElektricniGrelnik($options);
            default:
                throw new \Exception(sprintf('Generator : Vrsta "%s" ne obstaja', $type));
        }
    }
}
