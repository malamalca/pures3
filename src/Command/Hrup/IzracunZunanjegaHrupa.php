<?php
declare(strict_types=1);

namespace App\Command\Hrup;

use App\Calc\Hrup\ZunanjiHrup\Prostor;
use App\Core\App;
use App\Core\Command;

class IzracunZunanjegaHrupa extends Command
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

        $konstrukcije = App::loadProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'konstrukcije');
        $oknaVrata = App::loadProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'oknaVrata');

        /** @var array $prostoriIn */
        $prostoriIn = App::loadProjectData('Hrup', $projectId, 'zunanjiHrup');
        if ($prostoriIn) {
            if (!$this->validateSchema(json: $prostoriIn, schema: 'zunanjiHrup', area: 'Hrup')) {
                return;
            }

            $elementi = new \stdClass();
            $elementi->konstrukcije = $konstrukcije;
            $elementi->oknaVrata = $oknaVrata;

            $prostoriOut = [];
            foreach ($prostoriIn as $prostorConfig) {
                $prostor = new Prostor($elementi, $prostorConfig);
                $prostor->analiza($splosniPodatki);
                $prostoriOut[] = $prostor->export();
            }

            if (count($prostoriOut) == 0) {
                throw new \Exception('Prostori obstajajo.');
            }

            App::saveProjectCalculation('Hrup', $projectId, 'zunanjiHrup', $prostoriOut);
        }
    }
}
