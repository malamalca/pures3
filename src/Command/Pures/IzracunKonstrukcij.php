<?php
declare(strict_types=1);

namespace App\Command\Pures;

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

        /** @var \stdClass $splosniPodatki */
        $splosniPodatki = App::loadProjectData('Pures', $projectId, 'splosniPodatki');

        /** @var \stdClass $okolje */
        $okolje = App::loadProjectCalculation('Pures', $projectId, 'okolje');

        /** @var array $netransparentneKonstrukcije */
        $netransparentneKonstrukcije = App::loadProjectData(
            'Pures',
            $projectId,
            'konstrukcije' . DS . 'netransparentne'
        );
        if (!empty($netransparentneKonstrukcije)) {
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

            /** Knjižnica predefiniranih konstrukcij */
            $libraryArray = json_decode((string)file_get_contents(CONFIG . 'TSGKonstrukcije.json'));
            foreach ($libraryArray as $item) {
                CalcKonstrukcije::$library[$item->sifra] = $item;
            }

            /** Konstrukcije stavbe */
            $netransparentneKonsOut = [];
            foreach ($netransparentneKonstrukcije as $konstrukcija) {
                $netransparentneKonsOut[] = CalcKonstrukcije::konstrukcija($konstrukcija, $okolje);
            }
            App::saveProjectCalculation(
                'Pures',
                $projectId,
                'konstrukcije' . DS . 'netransparentne',
                $netransparentneKonsOut
            );

            /** Konstrukcije referenčne stavbe */
            if ($splosniPodatki->stavba->vrsta == 'zahtevna') {
                $netransparentneKonsOut = [];
                foreach ($netransparentneKonstrukcije as $konstrukcija) {
                    $netransparentneKonsOut[] =
                        CalcKonstrukcije::konstrukcija($konstrukcija, $okolje, ['referencnaStavba' => true]);
                }
                App::saveProjectCalculation(
                    'Pures',
                    $projectId,
                    'konstrukcije' . DS . 'netransparentne_ref',
                    $netransparentneKonsOut
                );
            }
        }

        /** @var array $transparentneKonstrukcije */
        $transparentneKonstrukcije = App::loadProjectData('Pures', $projectId, 'konstrukcije' . DS . 'transparentne');
        if (!empty($transparentneKonstrukcije)) {
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

            /** Konstrukcije stavbe */
            $transparentneKonsOut = [];
            foreach ($transparentneKonstrukcije as $konstrukcija) {
                $transparentneKonsOut[] = CalcKonstrukcije::transparentne($konstrukcija, $okolje);
            }
            App::saveProjectCalculation(
                'Pures',
                $projectId,
                'konstrukcije' . DS . 'transparentne',
                $transparentneKonsOut
            );

            /** Konstrukcije referenčne stavbe */
            if ($splosniPodatki->stavba->vrsta == 'zahtevna') {
                $transparentneKonsOut = [];
                foreach ($transparentneKonstrukcije as $konstrukcija) {
                    $transparentneKonsOut[] =
                        CalcKonstrukcije::transparentne($konstrukcija, $okolje, ['referencnaStavba' => true]);
                }
                App::saveProjectCalculation(
                    'Pures',
                    $projectId,
                    'konstrukcije' . DS . 'transparentne_ref',
                    $transparentneKonsOut
                );
            }
        }
    }
}
