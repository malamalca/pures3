<?php
declare(strict_types=1);

namespace App\Command\Pures;

use App\Calc\GF\Cone\Cona;
use App\Core\App;
use App\Core\Command;
use App\Core\Log;

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

        /** @var \stdClass $splosniPodatki */
        $splosniPodatki = App::loadProjectData('Pures', $projectId, 'splosniPodatki');

        /** @var \stdClass $okolje */
        $okolje = App::loadProjectCalculation('Pures', $projectId, 'okolje');

        $konstrukcije = new \stdClass();
        $konstrukcije->netransparentne =
            App::loadProjectCalculation('Pures', $projectId, 'konstrukcije' . DS . 'netransparentne');
        $konstrukcije->transparentne =
            App::loadProjectCalculation('Pures', $projectId, 'konstrukcije' . DS . 'transparentne');

        /** @var array $coneIn */
        $coneIn = App::loadProjectData('Pures', $projectId, 'cone');
        if ($coneIn) {
            if (!$this->validateSchema(json: $coneIn, schema: 'cone', area: 'Pures')) {
                throw new \Exception('Napake v opisu cone.');
            }

            $coneOut = [];
            foreach ($coneIn as $conaConfig) {
                $cona = new Cona($konstrukcije, $conaConfig);
                $cona->analiza($okolje);
                $coneOut[] = $cona->export();
            }

            App::saveProjectCalculation('Pures', $projectId, 'cone', $coneOut);
        } else {
            Log::info('Ni podatka o conah.');
        }

        if ($splosniPodatki->stavba->vrsta == 'zahtevna') {
            $referencneKonstrukcije = new \stdClass();
            $referencneKonstrukcije->netransparentne =
                App::loadProjectCalculation('Pures', $projectId, 'konstrukcije' . DS . 'netransparentne_ref');
            $referencneKonstrukcije->transparentne =
                App::loadProjectCalculation('Pures', $projectId, 'konstrukcije' . DS . 'transparentne_ref');

            $coneOut = [];
            foreach ($coneIn as $conaConfig) {
                $cona = new Cona($referencneKonstrukcije, $conaConfig, ['referencnaStavba' => true]);
                $cona->analiza($okolje);
                $coneOut[] = $cona->export();
            }

            App::saveProjectCalculation('Pures', $projectId, 'cone_ref', $coneOut);
        }
    }
}
