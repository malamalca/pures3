<?php
declare(strict_types=1);

namespace App\Command;

use App\Calc\GF\Cone\Cona;
use App\Core\App;
use App\Core\Command;
use JsonSchema\Validator;

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

        // validate input json
        $validator = new Validator();
        $schema = (string)file_get_contents(SCHEMAS . 'coneSchema.json');
        $validator->validate($coneIn, json_decode($schema));
        if (!$validator->isValid()) {
            $this->out('cone.json vsebuje napake:', 'error');
            foreach ($validator->getErrors() as $error) {
                $this->out(sprintf('[%s] %s', $error['property'], $error['message']), 'info');
            }

            return;
        }

        $coneOut = [];
        foreach ($coneIn as $conaConfig) {
            $cona = new Cona($conaConfig);
            $cona->analiza($okolje, $netransparentneKonstrukcije, $transparentneKonstrukcije);
            $coneOut[] = $cona->export();
        }

        if (count($coneOut) == 0) {
            throw new \Exception('Cone ne obstajajo.');
        }

        App::saveProjectCalculation($projectId, 'cone', $coneOut);
    }
}
