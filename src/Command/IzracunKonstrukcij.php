<?php
declare(strict_types=1);

namespace App\Command;

use App\Core\App;
use App\Core\Command;
use App\Lib\CalcKonstrukcije;

class IzracunKonstrukcij extends Command
{
    /**
     * Command run routine
     *
     * @param string|null $projectId Project id.
     * @return void
     */
    public function run($projectId = null)
    {
        parent::run();

        /** @var \stdClass $okolje */
        $okolje = App::loadProjectCalculation($projectId, 'okolje');

        /** @var array $netransparentneKonstrukcije */
        $netransparentneKonstrukcije = App::loadProjectData($projectId, 'konstrukcije' . DS . 'netransparentne');
        $netransparentneKonsOut = [];
        foreach ($netransparentneKonstrukcije as $konstrukcija) {
            $netransparentneKonsOut[] = CalcKonstrukcije::konstrukcija($konstrukcija, $okolje);
        }
        App::saveProjectCalculation($projectId, 'konstrukcije' . DS . 'netransparentne', $netransparentneKonsOut);

        /** @var array $transparentneKonstrukcije */
        $transparentneKonstrukcije = App::loadProjectData($projectId, 'konstrukcije' . DS . 'transparentne');
        $transparentneKonsOut = [];
        foreach ($transparentneKonstrukcije as $konstrukcija) {
            $transparentneKonsOut[] = CalcKonstrukcije::transparentne($konstrukcija, $okolje);
        }

        App::saveProjectCalculation($projectId, 'konstrukcije' . DS . 'transparentne', $transparentneKonsOut);
    }
}
