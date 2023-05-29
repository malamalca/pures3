<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class IzkaziController
{
    /**
     * Prvi del izkaza - splošni podatki
     *
     * @param string $projectName Building name
     * @return void
     */
    public function podrocjeGf($projectName)
    {
        App::set('stavba', App::loadProjectCalculation($projectName, 'stavba'));
        App::set('tKons', App::loadProjectCalculation($projectName, 'konstrukcije' . DS . 'transparentne'));
        App::set('ntKons', App::loadProjectCalculation($projectName, 'konstrukcije' . DS . 'netransparentne'));
        App::set('cone', App::loadProjectCalculation($projectName, 'cone'));
        App::set('okolje', App::loadProjectCalculation($projectName, 'okolje'));
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
