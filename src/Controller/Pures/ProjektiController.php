<?php
declare(strict_types=1);

namespace App\Controller\Pures;

use App\Core\App;
use App\Core\Controller;

class ProjektiController extends Controller
{
    /**
     * Prikaz seznama projektov
     *
     * @return void
     */
    public function index()
    {
        if (App::isLocalProject()) {
            if (file_exists(App::getLocalProjectPath() . DS . 'podatki' . DS . 'cone.json')) {
                App::redirect('/pures/projekti/view/env');
            } else {
                App::redirect('/hrup/projekti/view/env');
            }
        }
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

        App::set('sistemiOHT', App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'ogrevanje'));
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
     * @param ?string $ref Referenčna stavba
     * @return void
     */
    public function analiza($projectId, $ref = null)
    {
        App::set('projectId', $projectId);
        $splosniPodatki = App::loadProjectData('Pures', $projectId, 'splosniPodatki');
        App::set('splosniPodatki', $splosniPodatki);

        App::set('stavba', App::loadProjectCalculation('Pures', $projectId, 'stavba'));

        if ($splosniPodatki->stavba->vrsta == 'zahtevna') {
            App::set('refStavba', App::loadProjectCalculation('Pures', $projectId, 'Ref' . DS . 'stavba'));
        }
    }

    /**
     * Prikaz analize projekta s področja TSS
     *
     * @param string $projectId Building name
     * @param ?string $ref Referenčna stavba
     * @return void
     */
    public function snes($projectId, $ref = null)
    {
        App::set('projectId', $projectId);
        App::set('splosniPodatki', App::loadProjectData('Pures', $projectId, 'splosniPodatki'));
        App::set('stavba', App::loadProjectCalculation(
            'Pures',
            $projectId,
            ($ref == 'ref' ? 'Ref' . DS : '') . 'stavba'
        ));
        App::set('sistemi', App::loadProjectCalculation(
            'Pures',
            $projectId,
            ($ref == 'ref' ? 'Ref' . DS : '') . 'TSS' . DS . 'ogrevanje.json'
        ));
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

    /**
     * Izvoz XML za energetske izkaznice
     *
     * @param string|null $projectId Building name
     * @return void
     */
    public function ei($projectId = null)
    {
        App::set('projectId', $projectId);
        App::set('splosniPodatki', App::loadProjectData('Pures', $projectId, 'splosniPodatki'));
        App::set('okolje', App::loadProjectCalculation('Pures', $projectId, 'okolje'));
        App::set('stavba', App::loadProjectCalculation('Pures', $projectId, 'stavba'));
        App::set('cone', App::loadProjectCalculation('Pures', $projectId, 'cone'));
    }
}
