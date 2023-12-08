<?php
declare(strict_types=1);

namespace App\Command\Hrup;

use App\Core\App;
use App\Core\Command;
use App\Core\Configure;
use App\Core\PDF\PdfFactory;
use App\Core\PdfView;

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

        $view = new PdfView([], ['layout' => $pdfLayout]);
        $view->area = 'Hrup';
        $view->set('projectId', $projectId);
        $view->set('splosniPodatki', App::loadProjectData('Hrup', $projectId, 'splosniPodatki'));
        $view->set('konstrukcije', App::loadProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'konstrukcije'));
        $view->set('oknaVrata', App::loadProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'oknaVrata'));
        $view->set('prostori', App::loadProjectCalculation('Hrup', $projectId, 'zunanjiHrup'));
        $view->set('udarniHrup', App::loadProjectCalculation('Hrup', $projectId, 'udarniHrup'));
        $view->set('zracniHrup', App::loadProjectCalculation('Hrup', $projectId, 'zracniHrup'));
        $view->set('odmevniHrup', App::loadProjectCalculation('Hrup', $projectId, 'odmevniHrup'));
        $view->set('strojniHrup', App::loadProjectCalculation('Hrup', $projectId, 'strojniHrup'));

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

        $sourceFilename = App::getProjectFolder('Hrup', $projectId, 'podatki') . 'tehnicnoPorocilo.md';
        if (file_exists($sourceFilename)) {
            //$porocilo = file_get_contents($sourceFilename);
            ob_start();
            require_once $sourceFilename;
            $porocilo = ob_get_contents();
            ob_end_clean();
            if (!empty($porocilo)) {
                $porociloPages = explode('<!-- NEW PAGE -->', $porocilo);
                foreach ($porociloPages as $porociloPage) {
                    $view->set('porocilo', $porociloPage);
                    $porocilo = (string)$view->render('Projekti', 'porocilo');

                    $pdf->newPage($porocilo);
                }
            }
        }

        $pdf->newPage((string)$view->render('Projekti', 'konstrukcije'));

        if ($view->get('prostori')) {
            foreach ($view->get('prostori') as $prostor) {
                $view->set('prostor', $prostor);
                $pdf->newPage((string)$view->render('ZunanjiHrup', 'view'));
            }
        }

        if ($view->get('zracniHrup')) {
            foreach ($view->get('zracniHrup') as $locilnaKonstrukcija) {
                $view->set('locilnaKonstrukcija', $locilnaKonstrukcija);
                $pdf->newPage((string)$view->render('ZracniHrup', 'view'));
            }
        }

        if ($view->get('udarniHrup')) {
            foreach ($view->get('udarniHrup') as $locilnaKonstrukcija) {
                $view->set('locilnaKonstrukcija', $locilnaKonstrukcija);
                $pdf->newPage((string)$view->render('UdarniHrup', 'view'));
            }
        }

        $pdfFolder = App::getProjectFolder('Hrup', $projectId, 'pdf');
        if (!is_dir($pdfFolder)) {
            mkdir($pdfFolder, 0777, true);
        }

        $pdf->saveAs($pdfFolder . 'HrupElaborat.pdf');
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

        $pdf->saveAs($pdfFolder . 'HrupIzkaz.pdf');
    }
}
