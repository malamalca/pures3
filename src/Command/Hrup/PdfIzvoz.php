<?php
declare(strict_types=1);

namespace App\Command\Hrup;

use App\Core\App;
use App\Core\Command;
use App\Core\Configure;
use App\Core\PDF\PdfFactory;
use App\Core\View;

class PdfIzvoz extends Command
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

        $pdfEngine = Configure::read('PDF.engine');
        $pdfLayout = Configure::read('PDF.' . $pdfEngine . '.layout');

        $view = new View([], ['layout' => $pdfLayout]);
        $view->area = 'Hrup';
        $view->set('projectId', $projectId);
        $view->set('splosniPodatki', App::loadProjectData('Hrup', $projectId, 'splosniPodatki'));
        $view->set('prostori', App::loadProjectCalculation('Hrup', $projectId, 'zunanjiHrup'));

        $view->set('konstrukcije', App::loadProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'konstrukcije'));
        $view->set('oknaVrata', App::loadProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'oknaVrata'));

        $this->izkaz($projectId, $view);
        $this->elaborat($projectId, $view);
    }

    /**
     * Izvoz elaborata v pdf
     *
     * @param string $projectId Project id.
     * @param \App\Core\View $view View object
     * @return void
     */
    private function elaborat($projectId, $view)
    {
        $pdfEngine = Configure::read('PDF.engine');
        $pdf = PdfFactory::create($pdfEngine, Configure::read('PDF.' . $pdfEngine, []));

        $pdf->newPage((string)$view->render('Projekti', 'naslovnica'));
        $pdf->newPage((string)$view->render('Projekti', 'izjava'));
        $pdf->newPage((string)$view->render('Projekti', 'konstrukcije'));

        foreach ($view->get('prostori') as $prostor) {
            $view->set('prostor', $prostor);
            $pdf->newPage((string)$view->render('ZunanjiHrup', 'view'));
        }

        $sourceFilename = App::getProjectFolder('Hrup', $projectId, 'podatki') . 'tehnicnoPorocilo.txt';
        if (file_exists($sourceFilename)) {
            $porocilo = file_get_contents($sourceFilename);
            if (!empty($porocilo)) {
                $view->set('porocilo', $porocilo);
                $porocilo = (string)$view->render('Projekti', 'porocilo');
                $pdf->newPage($porocilo);
            }
        }

        $pdfFolder = App::getProjectFolder('Hrup', $projectId, 'pdf');
        if (!is_dir($pdfFolder)) {
            mkdir($pdfFolder, 0777, true);
        }

        $pdf->saveAs($pdfFolder . 'elaborat.pdf');
    }

    /**
     * Izvoz izkaza v pdf
     *
     * @param string $projectId Project id.
     * @param \App\Core\View $view View object.
     * @return void
     */
    private function izkaz($projectId, $view)
    {
        $pdfEngine = Configure::read('PDF.engine');
        $pdf = PdfFactory::create($pdfEngine, Configure::read('PDF.' . $pdfEngine, []));

        $pdf->newPage((string)$view->render('Projekti', 'izkaz'));

        $pdfFolder = App::getProjectFolder('Hrup', $projectId, 'pdf');
        if (!is_dir($pdfFolder)) {
            mkdir($pdfFolder, 0777, true);
        }

        $pdf->saveAs($pdfFolder . 'izkaz.pdf');
    }
}
