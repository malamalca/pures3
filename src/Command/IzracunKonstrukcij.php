<?php
declare(strict_types=1);

namespace App\Command;

use App\Core\App;
use App\Core\Command;
use App\Lib\CalcKonstrukcije;
use JsonSchema\Validator;

class IzracunKonstrukcij extends Command
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
        $netransparentneKonstrukcije = App::loadProjectData($projectId, 'konstrukcije' . DS . 'netransparentne');

        // validate input json
        $schema = (string)file_get_contents(SCHEMAS . 'netransparentneSchema.json');
        $validator = new Validator();
        $validator->validate($netransparentneKonstrukcije, json_decode($schema));
        if (!$validator->isValid()) {
            $this->out('netransparentne.json vsebuje napake:', 'error');
            foreach ($validator->getErrors() as $error) {
                $this->out(sprintf('[%s] %s', $error['property'], $error['message']), 'info');
            }

            return;
        }

        $netransparentneKonsOut = [];
        foreach ($netransparentneKonstrukcije as $konstrukcija) {
            $netransparentneKonsOut[] = CalcKonstrukcije::konstrukcija($konstrukcija, $okolje);
        }
        App::saveProjectCalculation($projectId, 'konstrukcije' . DS . 'netransparentne', $netransparentneKonsOut);

        /** @var array $transparentneKonstrukcije */
        $transparentneKonstrukcije = App::loadProjectData($projectId, 'konstrukcije' . DS . 'transparentne');

        // validate input json
        $validator = new Validator();
        $schema = (string)file_get_contents(SCHEMAS . 'oknavrataSchema.json');
        $validator->validate($transparentneKonstrukcije, json_decode($schema));
        if (!$validator->isValid()) {
            $this->out('oknavrata.json vsebuje napake:', 'error');
            foreach ($validator->getErrors() as $error) {
                $this->out(sprintf('[%s] %s', $error['property'], $error['message']), 'info');
            }

            return;
        }

        $transparentneKonsOut = [];
        foreach ($transparentneKonstrukcije as $konstrukcija) {
            $transparentneKonsOut[] = CalcKonstrukcije::transparentne($konstrukcija, $okolje);
        }

        App::saveProjectCalculation($projectId, 'konstrukcije' . DS . 'transparentne', $transparentneKonsOut);
    }
}
