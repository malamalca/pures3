<?php
declare(strict_types=1);

namespace App\Calc\TSS\OgrevalniSistemi\Podsistemi\KoncniPrenosniki\Izbire;

enum VrstaSistemaPloskovnihOgreval: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case TalnoOgrevanjeMokriSistem = 'talno_mokri';
    case TalnoOgrevanjeSuhiSistem = 'talno_suhi';
    case TalnoOgrevanjeSuhiSistemSTankoOblogo = 'talno_suhiTankaObloga';
    case StenskoOgrevanje = 'stensko';
    case StropnoOgrevanje = 'stopno';
}
