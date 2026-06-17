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
     * @param array|null $args Additional arguments
     * @return void
     */
    public function run($projectId = null, ...$args)
    {
        parent::run();

        /** @var \stdClass $splosniPodatki */
        $splosniPodatki = App::loadProjectData('Pures', $projectId, 'splosniPodatki');

        // leto projekta (zadnje 4 številke datuma), uporabljeno za referenčni svetlobni izkoristek (hL)
        $letoNiz = substr(trim((string)($splosniPodatki->datum ?? '')), -4);
        $letoProjekta = (int)$letoNiz;
        if (!ctype_digit($letoNiz) || $letoProjekta < 2000 || $letoProjekta > 2100) {
            throw new \Exception(sprintf(
                'Iz datuma projekta "%s" ni mogoče določiti veljavnega leta (zadnje 4 številke).',
                $splosniPodatki->datum ?? ''
            ));
        }

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
                $cona = new Cona($konstrukcije, $conaConfig, ['leto' => $letoProjekta]);
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
                App::loadProjectCalculation('Pures', $projectId, 'Ref' . DS . 'konstrukcije' . DS . 'netransparentne');
            $referencneKonstrukcije->transparentne =
                App::loadProjectCalculation('Pures', $projectId, 'Ref' . DS . 'konstrukcije' . DS . 'transparentne');

            $coneOut = [];
            foreach ($coneIn as $conaConfig) {
                $cona = new Cona(
                    $referencneKonstrukcije,
                    $conaConfig,
                    ['referencnaStavba' => true, 'leto' => $letoProjekta]
                );
                $cona->analiza($okolje);
                $coneOut[] = $cona->export();
            }

            App::saveProjectCalculation('Pures', $projectId, 'Ref' . DS . 'cone', $coneOut);
        }
    }
}
