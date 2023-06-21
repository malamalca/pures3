<?php
declare(strict_types=1);

namespace App\Command;

use App\Calc\GF\Cone\Cona;
use App\Core\App;
use App\Core\Command;

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
        foreach ($coneIn as $conaConfig) {
            $cona = new Cona($conaConfig);
            $cona->analiza($okolje, $netransparentneKonstrukcije, $transparentneKonstrukcije);
            $coneOut[] = $cona->export();

            /** @var \stdClass $cona */
            /*$coneOut[] = CalcCone::analizaCone(
                $conaConfig,
                $okolje,
                $netransparentneKonstrukcije,
                $transparentneKonstrukcije
            );*/
        }

        if (count($coneOut) == 0) {
            throw new \Exception('Cone ne obstajajo.');
        }

        App::saveProjectCalculation($projectId, 'cone', $coneOut);
    }
}
