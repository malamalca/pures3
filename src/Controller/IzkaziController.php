<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

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
        App::set('tKons', App::loadProjectCalculation($projectId, 'konstrukcije' . DS . 'transparentne') ?? []);
        App::set('ntKons', App::loadProjectCalculation($projectId, 'konstrukcije' . DS . 'netransparentne') ?? []);
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
}
