<?php
declare(strict_types=1);

namespace App\Test\Pures\TSS;

use App\Calc\GF\Cone\Cona;
use PHPUnit\Framework\TestCase;

final class TSSReferencnaStavbaTest extends TestCase
{
    public function testReferencnoPrezracevanje()
    {
        /** @var array $coneIn */
        $coneIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'podatki' . DS . 'cone.json'));

        /** @var array $konstrukcijeIn */
        $konstrukcijeIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'podatki' . DS . 'konstrukcije' . DS . 'netransparentne.json'));
        /** @var array $oknaVrataIn */
        $oknaVrataIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'podatki' . DS . 'konstrukcije' . DS . 'transparentne.json'));

        $konstrukcije = new \stdClass();
        $konstrukcije->netransparentne = $konstrukcijeIn;
        $konstrukcije->transparentne = $oknaVrataIn;

        $cona = new Cona($konstrukcije, $coneIn[0]);

        $tssPrezracevanjeIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'TSS' . DS . 'prezracevanje.json'));
        $tssPrezracevanje = $tssPrezracevanjeIn[0];

        $referencniTSS = $cona->referencniTSS('prezracevanje', $tssPrezracevanje);

        $this->assertFalse(empty($referencniTSS));
    }
}
