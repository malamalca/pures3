<?php
declare(strict_types=1);

namespace App\Command\Hrup;

use App\Calc\Hrup\Elementi\EnostavnaKonstrukcija;
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
        if (!empty($konstrukcijeIn)) {
            if (!$this->validateSchema(json: $konstrukcijeIn, schema: 'konstrukcije', area: 'Hrup')) {
                return;
            }

            $konstrukcijeOut = [];
            foreach ($konstrukcijeIn as $konstrukcijaConfig) {
                $konstrukcija = new EnostavnaKonstrukcija($konstrukcijaConfig);
                $konstrukcija->analiza();
                $konstrukcijeOut[] = $konstrukcija->export();
            }

            if (count($konstrukcijeOut) == 0) {
                throw new \Exception('Konstrukcije obstajajo.');
            }

            App::saveProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'konstrukcije', $konstrukcijeOut);
        }

        $oknaVrataIn = App::loadProjectData('Hrup', $projectId, 'elementi' . DS . 'oknaVrata');
        if (!empty($oknaVrataIn)) {
            if (!$this->validateSchema(json: $oknaVrataIn, schema: 'oknaVrata', area: 'Hrup')) {
                return;
            }

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
}
