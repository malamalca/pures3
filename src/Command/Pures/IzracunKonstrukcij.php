<?php
declare(strict_types=1);

namespace App\Command\Pures;

use App\Core\App;
use App\Core\Command;
use App\Lib\CalcKonstrukcije;

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
            if (!$this->validateSchema(json: $netransparentneKonstrukcije, schema: 'netransparentne', area: 'Pures')) {
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
            if (!$this->validateSchema(json: $transparentneKonstrukcije, schema: 'oknavrata', area: 'Pures')) {
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
