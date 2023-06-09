<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi\Podsistemi;

use App\Calc\TSS\OgrevalniSistemi\Podsistemi\Hranilniki\PosrednoOgrevanHranilnik;

class HranilnikFactory
{
    /**
     * Ustvari ustrezen generator glede na podan tip
     *
     * @param string $type Tip razvoda
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\TSS\OgrevalniSistemi\Podsistemi\Hranilniki\Hranilnik
     */
    public static function create($type, $options = null)
    {
        switch ($type) {
            case 'posrednoOgrevan':
                return new PosrednoOgrevanHranilnik($options);
            default:
                throw new \Exception(sprintf('Hranilnik : Vrsta "%s" ne obstaja', $type));
        }
    }
}
