<?php
declare(strict_types=1);

namespace App\Command;

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
        $view->set('projectId', $projectId);
        $view->set('splosniPodatki', App::loadProjectData($projectId, 'splosniPodatki'));
        $view->set('okolje', App::loadProjectCalculation($projectId, 'okolje'));
        $view->set('stavba', App::loadProjectCalculation($projectId, 'stavba'));
        $view->set('cone', App::loadProjectCalculation($projectId, 'cone'));
        $view->set('tKons', App::loadProjectCalculation($projectId, 'konstrukcije' . DS . 'transparentne'));
        $view->set('ntKons', App::loadProjectCalculation($projectId, 'konstrukcije' . DS . 'netransparentne'));
        $view->set('sistemiOgrevanja', App::loadProjectCalculation($projectId, 'TSS' . DS . 'ogrevanje'));
        $view->set('sistemiRazsvetljave', App::loadProjectCalculation($projectId, 'TSS' . DS . 'razsvetljava'));
        $view->set('sistemiPrezracevanja', App::loadProjectCalculation($projectId, 'TSS' . DS . 'prezracevanje'));
        $view->set('sistemiSTPE', App::loadProjectCalculation($projectId, 'TSS' . DS . 'fotovoltaika'));

        $tssFolder = App::getProjectFolder($projectId, 'izracuni') . 'TSS' . DS;
        $vgrajeniSistemi = array_filter((array)scandir($tssFolder), fn($d) => is_file($tssFolder . $d));
        $view->set('vgrajeniSistemi', array_map(
            fn($s) => substr((string)$s, 0, (int)strrpos((string)$s, '.')),
            $vgrajeniSistemi
        ));

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
        $splosniPodatki = $view->render('Projekti', 'view');
        $analizaProjekta = $view->render('Projekti', 'analiza');

        $pdfEngine = Configure::read('PDF.engine');
        $pdf = PdfFactory::create($pdfEngine, Configure::read('PDF.' . $pdfEngine, []));

        $pdf->newPage((string)$splosniPodatki);
        $pdf->newPage((string)$analizaProjekta);

        $pdfFolder = App::getProjectFolder($projectId, 'pdf');
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
        $splosniPodatki = $view->render('Izkazi', 'splosniPodatki');
        $podrocjeGf = $view->render('Izkazi', 'podrocjeGf');
        $podrocjeSnes = $view->render('Izkazi', 'podrocjeSNES');

        $pdfEngine = Configure::read('PDF.engine');
        $pdf = PdfFactory::create($pdfEngine, Configure::read('PDF.' . $pdfEngine, []));

        $pdf->newPage((string)$splosniPodatki);
        $pdf->newPage((string)$podrocjeGf);
        $pdf->newPage((string)$podrocjeSnes);

        $pdfFolder = App::getProjectFolder($projectId, 'pdf');
        if (!is_dir($pdfFolder)) {
            mkdir($pdfFolder, 0777, true);
        }

        $pdf->saveAs($pdfFolder . 'izkaz.pdf');
    }
}
