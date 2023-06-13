<?php
declare(strict_types=1);

namespace App\Command;

use App\Core\App;
use App\Core\Command;
use App\Core\Configure;
use App\Core\Pdf;
use App\Core\View;

class PdfIzkaz extends Command
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

        $view = new View([], ['layout' => 'pdf']);
        $view->set('splosniPodatki', App::loadProjectData($projectId, 'splosniPodatki'));
        $view->set('okolje', App::loadProjectCalculation($projectId, 'okolje'));
        $view->set('stavba', App::loadProjectCalculation($projectId, 'stavba'));
        $view->set('cone', App::loadProjectCalculation($projectId, 'cone'));
        $view->set('tKons', App::loadProjectCalculation($projectId, 'konstrukcije' . DS . 'transparentne'));
        $view->set('ntKons', App::loadProjectCalculation($projectId, 'konstrukcije' . DS . 'netransparentne'));
        $view->set('sistemiOgrevanja', App::loadProjectCalculation($projectId, 'TSS' . DS . 'ogrevanje'));
        $view->set('sistemiRazsvetljave', App::loadProjectCalculation($projectId, 'TSS' . DS . 'razsvetljava'));
        $view->set('sistemiPrezracevanja', App::loadProjectCalculation($projectId, 'TSS' . DS . 'prezracevanje'));

        $tssFolder = App::getProjectFolder($projectId, 'izracuni') . 'TSS' . DS;
        $vgrajeniSistemi = array_filter((array)scandir($tssFolder), fn($d) => is_file($tssFolder . $d));
        $view->set('vgrajeniSistemi', array_map(
            fn($s) => substr((string)$s, 0, (int)strrpos((string)$s, '.')),
            $vgrajeniSistemi
        ));

        $splosniPodatki = $view->render('Izkazi', 'splosniPodatki');
        $podrocjeGf = $view->render('Izkazi', 'podrocjeGf');
        $podrocjeSnes = $view->render('Izkazi', 'podrocjeSNES');

        $pdf = new Pdf(Configure::read('Pdf', []));
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
