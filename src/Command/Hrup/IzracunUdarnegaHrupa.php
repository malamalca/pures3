<?php
declare(strict_types=1);

namespace App\Command\Hrup;

use App\Calc\Hrup\UdarniHrup\UdarniHrupPoenostavljen;
use App\Core\App;
use App\Core\Command;

class IzracunUdarnegaHrupa extends Command
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
        $splosniPodatki = App::loadProjectData('Hrup', $projectId, 'splosniPodatki');
        if (!$this->validateSchema(json: $splosniPodatki, schema: 'splosniPodatki', area: 'Hrup')) {
            return;
        }

        $konstrukcijeLib = App::loadProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'konstrukcije');

        /** @var array $hrupIn */
        $hrupIn = App::loadProjectData('Hrup', $projectId, 'udarniHrup');
        //if (!$this->validateSchema(json: $prostoriIn, schema: 'zunanjiHrup', area: 'Hrup')) {
        //    return;
        //}

        if ($hrupIn) {
            $hrupOut = [];
            foreach ($hrupIn as $konstrukcijaConfig) {
                $konstrukcija = new UdarniHrupPoenostavljen($konstrukcijeLib, $konstrukcijaConfig);
                $konstrukcija->analiza($splosniPodatki);
                $hrupOut[] = $konstrukcija->export();
            }

            App::saveProjectCalculation('Hrup', $projectId, 'udarniHrup', $hrupOut);
        }
    }
}
