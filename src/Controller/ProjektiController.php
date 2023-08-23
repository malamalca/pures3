<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class ProjektiController
{
    /**
     * Prikaz seznama projektov
     *
     * @return void
     */
    public function index()
    {
        $dirs = array_filter((array)scandir(PROJECTS), fn($d) => is_dir(PROJECTS . $d) && !in_array($d, ['.', '..']));
        App::set('dirs', $dirs);
    }

    /**
     * Prikaz splosnih podatkov projekta
     *
     * @param string|null $projectId Building name
     * @return void
     */
    public function view($projectId = null)
    {
        App::set('projectId', $projectId);
        App::set('splosniPodatki', App::loadProjectData($projectId, 'splosniPodatki'));
        App::set('okolje', App::loadProjectCalculation($projectId, 'okolje'));
        App::set('stavba', App::loadProjectCalculation($projectId, 'stavba'));
        App::set('cone', App::loadProjectCalculation($projectId, 'cone'));

        App::set('sistemiOgrevanja', App::loadProjectCalculation($projectId, 'TSS' . DS . 'ogrevanje'));
        App::set('sistemiPrezracevanja', App::loadProjectCalculation($projectId, 'TSS' . DS . 'prezracevanje'));
        App::set('sistemiRazsvetljave', App::loadProjectCalculation($projectId, 'TSS' . DS . 'razsvetljava'));
        App::set('sistemiSTPE', App::loadProjectCalculation($projectId, 'TSS' . DS . 'fotovoltaika'));
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

    /**
     * Prikaz naslovnice
     *
     * @param string $projectId Building name
     * @return void
     */
    public function naslovnica($projectId)
    {
        App::set('projectId', $projectId);
        App::set('splosniPodatki', App::loadProjectData($projectId, 'splosniPodatki'));
    }
}
