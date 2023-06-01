<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class ProjektiController
{
    /**
     * Prikaz splosnih podatkov projekta
     *
     * @param string|null $projectId Building name
     * @return void
     */
    public function view($projectId = null)
    {
        if (empty($projectId)) {
            App::redirect(App::url('/projects/index'));
        }

        App::set('projectId', $projectId);
        App::set('splosniPodatki', App::loadProjectData($projectId, 'splosniPodatki'));
        App::set('stavba', App::loadProjectCalculation($projectId, 'stavba'));
        App::set('cone', App::loadProjectCalculation($projectId, 'cone'));

        App::set('ogrevanje', App::loadProjectCalculation($projectId, 'TSS' . DS . 'ogrevanje'));
        App::set('prezracevanje', App::loadProjectCalculation($projectId, 'TSS' . DS . 'prezracevanje'));
        App::set('razsvetljava', App::loadProjectCalculation($projectId, 'TSS' . DS . 'razsvetljava'));
    }

    /**
     * Prikaz analize projekta s področja gradbene fizike
     *
     * @param string $projectId Building name
     * @return void
     */
    public function analiza($projectId)
    {
        App::set('projectId', $projectId);
        App::set('splosniPodatki', App::loadProjectData($projectId, 'splosniPodatki'));
        App::set('stavba', App::loadProjectCalculation($projectId, 'stavba'));
        App::set('stavba', App::loadProjectCalculation($projectId, 'stavba'));
    }

    /**
     * Prikaz analize projekta s področja TSS
     *
     * @param string $projectId Building name
     * @return void
     */
    public function snes($projectId)
    {
        App::set('projectId', $projectId);
        App::set('splosniPodatki', App::loadProjectData($projectId, 'splosniPodatki'));
        App::set('stavba', App::loadProjectCalculation($projectId, 'stavba'));
        App::set('sistemi', App::loadProjectCalculation($projectId, 'TSS' . DS . 'ogrevanje.json'));
    }
}
