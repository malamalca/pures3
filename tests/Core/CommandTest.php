<?php
declare(strict_types=1);

namespace App\Test\Core;

use App\Core\Command;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    public function testHasSwitchPrepoznaObeObliki(): void
    {
        $command = new Command();

        $this->assertTrue($command->hasSwitch(['--noPdf'], 'noPdf'));
        $this->assertTrue($command->hasSwitch(['--no-pdf'], 'noPdf'));
        $this->assertTrue($command->hasSwitch(['--nopdf'], 'noPdf'));
        $this->assertTrue($command->hasSwitch(['--NO-PDF'], 'noPdf'));
        $this->assertTrue($command->hasSwitch(['TestniProjekt', '--no-pdf'], 'noPdf'));
    }

    public function testHasSwitchBrezStikala(): void
    {
        $command = new Command();

        $this->assertFalse($command->hasSwitch([], 'noPdf'));
        $this->assertFalse($command->hasSwitch(['TestniProjekt'], 'noPdf'));
        $this->assertFalse($command->hasSwitch(['--pdf'], 'noPdf'));
    }

    public function testHasSwitchZahtevaVodilniPomisljaj(): void
    {
        $command = new Command();

        // ime projekta se ne sme razumeti kot stikalo
        $this->assertFalse($command->hasSwitch(['noPdf'], 'noPdf'));
        $this->assertFalse($command->hasSwitch([null, 'no-pdf'], 'noPdf'));
    }
}
