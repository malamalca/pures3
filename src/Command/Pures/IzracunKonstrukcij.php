<?php
declare(strict_types=1);

namespace App\Command\Pures;

use App\Core\App;
use App\Core\Command;
use App\Core\Configure;
use App\Core\PDF\PdfFactory;
use App\Core\PdfView;
use App\Lib\CalcKonstrukcije;

class IzracunKonstrukcij extends Command
{
    /**
     * Command run routine
     *
     * @param string|null $projectId Project id.
     * @param array|null $args Additional argumetns
     * @return void
     */
    public function run($projectId = null, ...$args)
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
                    'Ref' . DS . 'konstrukcije' . DS . 'netransparentne',
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
                    'Ref' . DS . 'konstrukcije' . DS . 'transparentne',
                    $transparentneKonsOut
                );
            }
        }

        if (count($args) > 0 && in_array('--pdf', $args)) {
            $this->pdfIzvoz($projectId);
        }
    }

    /**
     * Rutina za izvod pdf s konstrukcijami
     *
     * @param string $projectId Project id.
     * @return void
     */
    private function pdfIzvoz($projectId)
    {
        $pdfEngine = Configure::read('PDF.engine');
        $pdfLayout = Configure::read('PDF.' . $pdfEngine . '.layout');

        $pdf = PdfFactory::create($pdfEngine, Configure::read('PDF.' . $pdfEngine, []));

        $view = new PdfView([], ['layout' => $pdfLayout]);
        $view->set('okolje', App::loadProjectCalculation('Pures', $projectId, 'okolje'));
        $view->set(
            'ntKons',
            App::loadProjectCalculation('Pures', $projectId, 'konstrukcije' . DS . 'netransparentne') ?? []
        );

        foreach ($view->get('ntKons') as $kons) {
            $view->set('kons', $kons);
            $pdf->newPage((string)$view->render('Konstrukcije', 'view'));
        }

        $pdfFolder = App::getProjectFolder('Pures', $projectId, 'pdf');
        if (!is_dir($pdfFolder)) {
            mkdir($pdfFolder, 0777, true);
        }

        $pdf->saveAs($pdfFolder . 'PuresKonstrukcije.pdf');
    }
}
