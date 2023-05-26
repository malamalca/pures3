<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class IzkaziController
{
    /**
     * Prvi del izkaza - splošni podatki
     *
     * @param string $buildingName Building name
     * @return void
     */
    public function view($buildingName)
    {
        $splosniPodatkiFilename = PROJECTS . $buildingName . DS . 'podatki' . DS . 'splosniPodatki.json';
        $splosniPodatki = json_decode(file_get_contents($splosniPodatkiFilename));

        App::set('splosniPodatki', $splosniPodatki);

        $TSS = [];
        $TSSFolder = PROJECTS . $buildingName . DS . 'podatki' . DS . 'TSS';
        $TSSDirFiles = new \DirectoryIterator($TSSFolder);

        foreach ($TSSDirFiles as $fileinfo) {
            if (!$fileinfo->isDot()) {
                $TSS[$fileinfo->getBasename('.json')] = json_decode(
                    file_get_contents($TSSFolder . DS . $fileinfo->getFileName())
                );
            }
        }

        App::set('TSS', $TSS);

        $coneFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'cone.json';
        $cone = json_decode(file_get_contents($coneFile));
    }

    /**
     * Prvi del izkaza - splošni podatki
     *
     * @param string $buildingName Building name
     * @return void
     */
    public function podrocjeGf($buildingName)
    {
        $okoljeFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'okolje.json';
        $okolje = json_decode(file_get_contents($okoljeFile));

        $stavbaFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'stavba.json';
        $stavba = json_decode(file_get_contents($stavbaFile));

        $coneFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'cone.json';
        $cone = json_decode(file_get_contents($coneFile));

        $ntKonsFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'konstrukcije' . DS . 'netransparentne.json';
        $ntKons = json_decode(file_get_contents($ntKonsFile));

        $tKonsFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'konstrukcije' . DS . 'transparentne.json';
        $tKons = json_decode(file_get_contents($tKonsFile));

        App::set('stavba', $stavba);
        App::set('tKons', $tKons);
        App::set('ntKons', $ntKons);
        App::set('cone', $cone);
        App::set('okolje', $okolje);
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
