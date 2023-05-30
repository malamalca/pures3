<?php
declare(strict_types=1);

namespace App\Command;

use App\Core\App;
use App\Core\Command;
use App\Lib\CalcStavba;

class IzracunStavbe extends Command
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

        /** @var \stdClass $splosniPodatki */
        $splosniPodatki = App::loadProjectData($projectId, 'splosniPodatki');

        /** @var \stdClass $okolje */
        $okolje = App::loadProjectCalculation($projectId, 'okolje');

        /** @var array $cone */
        $cone = App::loadProjectCalculation($projectId, 'cone');

        $stavba = CalcStavba::analiza($cone, $okolje, $splosniPodatki);

        App::saveProjectCalculation($projectId, 'stavba', $stavba);
    }
}
