<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

class KlasifikacijaConeFactory
{
    /**
     * Ustvari stavbo glede na podan tip
     *
     * @param string $type Vrsta cone
     * @param \stdClass|null $options Dodatne nastavitve
     * @return \App\Calc\GF\Cone\KlasifikacijeCone\KlasifikacijaCone|null
     */
    public static function create(string $type, $options = null)
    {
        switch ($type) {
            case 'St-1':
                return new EnostanovanjskaKlasifikacijaCone($type, $options);
            case 'St-2':
            case 'St-3':
                return new VecstanovanjskaKlasifikacijaCone($type, $options);
            case 'Po-1':
            case 'Ho-1':
                return new PoslovnaKlasifikacijaCone($type, $options);
            case 'Kn-1':
                return new KnjizniceMuzejiArhiviKlasifikacijaCone($type, $options);
            case 'Go-1':
                return new GostinskaKlasifikacijaCone($type, $options);
            default:
                throw new \Exception(sprintf('Vrsta klasifikacije cone "%s" ne obstaja.', $type));
        }
    }
}
