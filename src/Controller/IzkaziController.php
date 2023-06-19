<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;
use App\Core\Configure;
use App\Core\Pdf;
use App\Core\View;

class IzkaziController
{
    /**
     * Prvi del izkaza - splošni podatki
     *
     * @param string $projectId Building name
     * @return void
     */
    public function splosniPodatki($projectId)
    {
        App::set('splosniPodatki', App::loadProjectData($projectId, 'splosniPodatki'));
        App::set('stavba', App::loadProjectCalculation($projectId, 'stavba'));
        App::set('cone', App::loadProjectCalculation($projectId, 'cone'));
        App::set('okolje', App::loadProjectCalculation($projectId, 'okolje'));

        $tssFolder = App::getProjectFolder($projectId, 'izracuni') . 'TSS' . DS;
        App::set('vgrajeniSistemi', array_map(
            fn($s) => substr((string)$s, 0, (int)strrpos((string)$s, '.')),
            array_filter((array)scandir($tssFolder), fn($d) => is_file($tssFolder . $d))
        ));
    }

    /**
     * Prvi del izkaza - splošni podatki
     *
     * @param string $projectId Building name
     * @return void
     */
    public function podrocjeGf($projectId)
    {
        App::set('stavba', App::loadProjectCalculation($projectId, 'stavba'));
        App::set('tKons', App::loadProjectCalculation($projectId, 'konstrukcije' . DS . 'transparentne'));
        App::set('ntKons', App::loadProjectCalculation($projectId, 'konstrukcije' . DS . 'netransparentne'));
        App::set('cone', App::loadProjectCalculation($projectId, 'cone'));
        App::set('okolje', App::loadProjectCalculation($projectId, 'okolje'));
    }

    /**
     * Drugi del izkaza - sNes
     *
     * @param string $projectId Building name
     * @return void
     */
    public function podrocjeSNES($projectId)
    {
        App::set('stavba', App::loadProjectCalculation($projectId, 'stavba'));
        App::set('cone', App::loadProjectCalculation($projectId, 'cone'));
        App::set('sistemiOgrevanja', App::loadProjectCalculation($projectId, 'TSS' . DS . 'ogrevanje'));
        App::set('sistemiRazsvetljave', App::loadProjectCalculation($projectId, 'TSS' . DS . 'razsvetljava'));
        App::set('sistemiPrezracevanja', App::loadProjectCalculation($projectId, 'TSS' . DS . 'prezracevanje'));
    }

    /**
     * Dfg
     *
     * @param string $projectId Building name
     * @return mixed
     */
    public function pdf($projectId)
    {
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

        return $pdf->render();
    }

    /**
     * Display graph
     *
     * @return void
     */
    public function graf()
    {
        //$compareWithKI = true;
        $primerFilename = 'TSG004.php';
        //$primerFilename = 'ISO13788_C2.php';
        //$primerFilename = 'KondVConi.php';

        $konstrukcijaJson = '{}';
        $okolje = new \stdClass();
        require dirname(__FILE__) . DS . 'primeri' . DS . $primerFilename;

        $konstrukcija = json_decode($konstrukcijaJson);

        $kons = \App\Lib\CalcKonstrukcije::konstrukcija($konstrukcija, $okolje, ['izracunKondenzacije' => true]);

        App::set('kons', $kons);
        App::set('okolje', $okolje);
    }
}
