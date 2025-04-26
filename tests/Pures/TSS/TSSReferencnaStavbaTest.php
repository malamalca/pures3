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
        $cona = new Cona(null, $coneIn[0]);

        $tssPrezracevanjeIn = json_decode(file_get_contents(PROJECTS . 'Pures' . DS . 'TestniProjekt' . DS . 'izracuni' . DS . 'TSS' . DS . 'prezracevanje.json'));
        $tssPrezracevanje = $tssPrezracevanjeIn[0];

        $referencniTSS = $cona->referencniTSS('prezracevanje', $tssPrezracevanje);

        $this->assertFalse(empty($referencniTSS));
    }
}
