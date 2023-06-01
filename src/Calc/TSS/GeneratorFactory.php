<?php
declare(strict_types=1);

namespace App\Calc\TSS;

use App\Calc\TSS\Generatorji\ToplotnaCrpalkaZrakVoda;
use App\Calc\TSS\Generatorji\ToplotnaCrpalkaZrakVodaSTV;

class GeneratorFactory
{
    /**
     * Ustvari ustrezen generator glede na podan tip
     *
     * @param string $type Tip razvoda
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\TSS\Generatorji\Generator
     */
    public static function create($type, $options = null)
    {
        switch ($type) {
            case 'TC_zrakvoda':
                return new ToplotnaCrpalkaZrakVoda($options);
            case 'TC_zrakvodaSTV':
                return new ToplotnaCrpalkaZrakVodaSTV($options);
            default:
                throw new \Exception(sprintf('Generator : Vrsta "%s" ne obstaja', $type));
        }
    }
}
