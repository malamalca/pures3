<?php
declare(strict_types=1);

namespace App\Controller\Pures;

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
        App::set('splosniPodatki', App::loadProjectData('Pures', $projectId, 'splosniPodatki'));
        App::set('stavba', App::loadProjectCalculation('Pures', $projectId, 'stavba'));
        App::set('cone', App::loadProjectCalculation('Pures', $projectId, 'cone'));
        App::set('okolje', App::loadProjectCalculation('Pures', $projectId, 'okolje'));

        $tssFolder = App::getProjectFolder('Pures', $projectId, 'izracuni') . 'TSS' . DS;
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
        App::set('stavba', App::loadProjectCalculation('Pures', $projectId, 'stavba'));
        App::set(
            'tKons',
            App::loadProjectCalculation('Pures', $projectId, 'konstrukcije' . DS . 'transparentne') ?? []
        );
        App::set(
            'ntKons',
            App::loadProjectCalculation('Pures', $projectId, 'konstrukcije' . DS . 'netransparentne') ?? []
        );
        App::set('cone', App::loadProjectCalculation('Pures', $projectId, 'cone'));
        App::set('okolje', App::loadProjectCalculation('Pures', $projectId, 'okolje'));
    }

    /**
     * Drugi del izkaza - sNes
     *
     * @param string $projectId Building name
     * @return void
     */
    public function podrocjeSNES($projectId)
    {
        App::set('stavba', App::loadProjectCalculation('Pures', $projectId, 'stavba'));
        App::set('cone', App::loadProjectCalculation('Pures', $projectId, 'cone'));
        App::set('sistemiOgrevanja', App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'ogrevanje'));
        App::set('sistemiRazsvetljave', App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'razsvetljava'));
        App::set(
            'sistemiPrezracevanja',
            App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'prezracevanje')
        );
    }
}
