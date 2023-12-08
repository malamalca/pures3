<?php
declare(strict_types=1);

namespace App\Controller\Pures;

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
        $baseDir = PROJECTS . 'Pures' . DS;
        $dirs = array_filter((array)scandir($baseDir), fn($d) => is_dir($baseDir . $d) && !in_array($d, ['.', '..']));
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
        App::set('splosniPodatki', App::loadProjectData('Pures', $projectId, 'splosniPodatki'));
        App::set('okolje', App::loadProjectCalculation('Pures', $projectId, 'okolje'));
        App::set('stavba', App::loadProjectCalculation('Pures', $projectId, 'stavba'));
        App::set('cone', App::loadProjectCalculation('Pures', $projectId, 'cone'));

        App::set('sistemiOgrevanja', App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'ogrevanje'));
        App::set(
            'sistemiPrezracevanja',
            App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'prezracevanje')
        );
        App::set('sistemiRazsvetljave', App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'razsvetljava'));
        App::set('sistemiSTPE', App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'fotovoltaika'));
    }

    /**
     * Prikaz tehničnega poročila
     *
     * @param string|null $projectId Building name
     * @return void
     */
    public function porocilo($projectId = null)
    {
        $this->view($projectId);

        $sourceFolder = App::getProjectFolder('Pures', $projectId, 'podatki');
        $sourceFilename = $sourceFolder . 'tehnicnoPorocilo.md';

        $porocilo = '';
        if (file_exists($sourceFilename)) {
            //$porocilo = file_get_contents($sourceFilename);
            ob_start();
            require_once $sourceFilename;
            $porocilo = ob_get_contents();
            ob_end_clean();
        }

        App::set('projectId', $projectId);
        App::set('porocilo', $porocilo);
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
        App::set('splosniPodatki', App::loadProjectData('Pures', $projectId, 'splosniPodatki'));
        App::set('stavba', App::loadProjectCalculation('Pures', $projectId, 'stavba'));
        App::set('stavba', App::loadProjectCalculation('Pures', $projectId, 'stavba'));
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
        App::set('splosniPodatki', App::loadProjectData('Pures', $projectId, 'splosniPodatki'));
        App::set('stavba', App::loadProjectCalculation('Pures', $projectId, 'stavba'));
        App::set('sistemi', App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'ogrevanje.json'));
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
        App::set('splosniPodatki', App::loadProjectData('Pures', $projectId, 'splosniPodatki'));
    }
}
