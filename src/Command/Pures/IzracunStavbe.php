<?php
declare(strict_types=1);

namespace App\Command\Pures;

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
        $splosniPodatki = App::loadProjectData('Pures', $projectId, 'splosniPodatki');

        /** @var \stdClass $okolje */
        $okolje = App::loadProjectCalculation('Pures', $projectId, 'okolje');

        $stavba = StavbaFactory::create($splosniPodatki->stavba->vrsta, $splosniPodatki->stavba);
        $stavba->cone = App::loadProjectCalculation('Pures', $projectId, 'cone');
        $stavba->analiza($okolje);
        $stavba->sistemi = App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS);
        $stavba->analizaTSS();
        $stavbaJson = $stavba->export();

        App::saveProjectCalculation('Pures', $projectId, 'stavba', $stavbaJson);
    }
}
