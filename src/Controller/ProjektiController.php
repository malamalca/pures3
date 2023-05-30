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
     * @param string $projectName Building name
     * @return void
     */
    public function snes($projectName)
    {
        App::set('splosniPodatki', App::loadProjectData($projectName, 'splosniPodatki'));
        App::set('stavba', App::loadProjectCalculation($projectName, 'stavba'));
        App::set('sistemi', App::loadProjectCalculation($projectName, 'TSS' . DS . 'ogrevanje.json'));
    }
}
