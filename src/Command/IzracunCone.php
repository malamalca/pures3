<?php
declare(strict_types=1);

namespace App\Command;

use App\Core\App;
use App\Core\Command;
use App\Lib\CalcCone;

class IzracunCone extends Command
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
        $netransparentneKonstrukcije = App::loadProjectCalculation($projectId, 'konstrukcije' . DS . 'netransparentne');

        /** @var array $transparentneKonstrukcije */
        $transparentneKonstrukcije = App::loadProjectCalculation($projectId, 'konstrukcije' . DS . 'transparentne');

        /** @var array $coneIn */
        $coneIn = App::loadProjectData($projectId, 'cone');

        $coneOut = [];
        foreach ($coneIn as $cona) {
            /** @var \stdClass $cona */
            $coneOut[] = CalcCone::analizaCone(
                $cona,
                $okolje,
                $netransparentneKonstrukcije,
                $transparentneKonstrukcije
            );
        }

        if (count($coneOut) == 0) {
            throw new \Exception('Cone ne obstajajo.');
        }

        App::saveProjectCalculation($projectId, 'cone', $coneOut);
    }
}
