<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS\OgrevalniSistemi\Podsistemi\Generatorji\Izbire;

enum VrstaLokacijeNamestitve: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case OgrevanProstor = 'ogrevanProstor';
    case Kotlovnica = 'kotlovnica';
    case NeogrevanProstor = 'neogrevanProstor'; // tudi v okolici
}
