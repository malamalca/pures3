<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;
use App\Core\Configure;

class IzkaziController
{
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
        $view = new \App\Core\View([], ['layout' => 'pdf']);
        $view->set('okolje', App::loadProjectCalculation($projectId, 'okolje'));
        $view->set('stavba', App::loadProjectCalculation($projectId, 'stavba'));
        $view->set('cone', App::loadProjectCalculation($projectId, 'cone'));
        $view->set('tKons', App::loadProjectCalculation($projectId, 'konstrukcije' . DS . 'transparentne'));
        $view->set('ntKons', App::loadProjectCalculation($projectId, 'konstrukcije' . DS . 'netransparentne'));
        $view->set('sistemiOgrevanja', App::loadProjectCalculation($projectId, 'TSS' . DS . 'ogrevanje'));
        $view->set('sistemiRazsvetljave', App::loadProjectCalculation($projectId, 'TSS' . DS . 'razsvetljava'));
        $view->set('sistemiPrezracevanja', App::loadProjectCalculation($projectId, 'TSS' . DS . 'prezracevanje'));

        $podrocjeGf = $view->render('Izkazi', 'podrocjeGf');
        $podrocjeSnes = $view->render('Izkazi', 'podrocjeSNES');

        $pdf = new \App\Core\TCPDFEngine(Configure::read('Pdf', []));
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
        $compareWithKI = true;
        $primerFilename = 'TSG004.php';
        //$primerFilename = 'ISO13788_C2.php';
        //$primerFilename = 'KondVConi.php';

        $konstrukcijaJson = '{}';
        $okolje = new \stdClass();
        require dirname(__FILE__) . DS . 'primeri' . DS . $primerFilename;

        $konstrukcija = json_decode($konstrukcijaJson);

        $kons = \App\Lib\CalcKonstrukcije::konstrukcija($konstrukcija, $okolje);

        App::set('kons', $kons);
        App::set('okolje', $okolje);
    }
}
