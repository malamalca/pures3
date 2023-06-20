<?php
declare(strict_types=1);

namespace App\Command;

use App\Calc\GF\StavbaFactory;
use App\Core\App;
use App\Core\Command;

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

        $stavba = StavbaFactory::create($splosniPodatki->stavba->vrsta, $splosniPodatki->stavba);
        $stavba->cone = App::loadProjectCalculation($projectId, 'cone');
        $stavba->analiza($okolje);
        $stavba->sistemi = App::loadProjectCalculation($projectId, 'TSS' . DS);
        $stavba->analizaTSS();
        $stavbaJson = $stavba->export();

        App::saveProjectCalculation($projectId, 'stavba', $stavbaJson);
    }
}
