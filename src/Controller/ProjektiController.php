<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class ProjektiController
{
    /**
     * Prikaz splosnih podatkov projekta
     *
     * @param string|null $projectName Building name
     * @return void
     */
    public function view($projectName = null)
    {
        if (empty($projectName)) {
            App::redirect(App::url('/projects/index'));
        }

        App::set('projectName', $projectName);
        App::set('splosniPodatki', App::loadProjectData($projectName, 'splosniPodatki'));
        App::set('stavba', App::loadProjectCalculation($projectName, 'stavba'));
        App::set('cone', App::loadProjectCalculation($projectName, 'cone'));
    }

    /**
     * Prikaz analize projekta s področja gradbene fizike
     *
     * @param string $projectName Building name
     * @return void
     */
    public function analiza($projectName)
    {
        App::set('splosniPodatki', App::loadProjectData($projectName, 'splosniPodatki'));
        App::set('stavba', App::loadProjectCalculation($projectName, 'stavba'));
    }

    /**
     * Prikaz analize projekta s področja TSS
     *
     * @param string $buildingName Building name
     * @return void
     */
    public function snes($buildingName)
    {
        $splosniPodatkiFile = PROJECTS . $buildingName . DS . 'podatki' . DS . 'splosniPodatki.json';
        $splosniPodatki = json_decode(file_get_contents($splosniPodatkiFile));

        App::set('splosniPodatki', $splosniPodatki);

        $stavbaFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'stavba.json';
        $stavba = json_decode(file_get_contents($stavbaFile));

        App::set('stavba', $stavba);

        $ogrevanjeFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'TSS' . DS . 'ogrevanje.json';
        $sistemi = json_decode(file_get_contents($ogrevanjeFile));

        App::set('sistemi', $sistemi);
    }
}
