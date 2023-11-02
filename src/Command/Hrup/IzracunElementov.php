<?php
declare(strict_types=1);

namespace App\Command\Hrup;

use App\Calc\Hrup\Elementi\Konstrukcija;
use App\Calc\Hrup\Elementi\OknaVrata;
use App\Core\App;
use App\Core\Command;

class IzracunElementov extends Command
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

        $konstrukcijeIn = App::loadProjectData('Hrup', $projectId, 'elementi' . DS . 'konstrukcije');

        $konstrukcijeOut = [];
        foreach ($konstrukcijeIn as $konstrukcijaConfig) {
            $konstrukcija = new Konstrukcija($konstrukcijaConfig);
            $konstrukcija->analiza();
            $konstrukcijeOut[] = $konstrukcija->export();
        }

        if (count($konstrukcijeOut) == 0) {
            throw new \Exception('Konstrukcije obstajajo.');
        }

        App::saveProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'konstrukcije', $konstrukcijeOut);

        $oknaVrataIn = App::loadProjectData('Hrup', $projectId, 'elementi' . DS . 'oknaVrata');

        $oknaVrataOut = [];
        foreach ($oknaVrataIn as $oknaVrataConfig) {
            $oknaVrata = new OknaVrata($oknaVrataConfig);
            $oknaVrata->analiza();
            $oknaVrataOut[] = $oknaVrata->export();
        }

        if (count($oknaVrataOut) == 0) {
            throw new \Exception('OknaVrata ne obstajajo.');
        }

        App::saveProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'oknaVrata', $oknaVrataOut);
    }
}
