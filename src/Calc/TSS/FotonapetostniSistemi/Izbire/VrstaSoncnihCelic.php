<?php
declare(strict_types=1);

namespace App\Calc\TSS\FotonapetostniSistemi\Izbire;

enum VrstaSoncnihCelic: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case MonokristalneSilicijeve = 'monokristalne';
    case PolikristalneSilicijeve = 'polikristalne';
    case AmorfniSilicij = 'amorfne';
    case CuInGaSe = 'CuInGaSe';
    case CdTe = 'CdTe';
    case Ostale = 'ostale';

    /**
     * Vrne koeficient glede na vrsto celic
     *
     * @return float
     */
    public function koeficientMoci()
    {
        $KpfLookup = [0.2, 0.18, 0.1, 0.105, 0.095, 0.035];

        return $KpfLookup[$this->getOrdinal()];
    }
}
