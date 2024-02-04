<?php
declare(strict_types=1);

namespace App\Controller\Pures;

use App\Core\App;

class TSSController
{
    /**
     * Prikaz podatkov o ovoju cone
     *
     * @param string $projectId Building name
     * @param string $sistemId Id
     * @return void
     */
    public function prezracevanje($projectId, $sistemId)
    {
        App::set('projectId', $projectId);

        $sistemi = App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'prezracevanje');
        App::set('sistemi', $sistemi);
        App::set('sistem', array_first($sistemi, fn($sistem) => strtolower($sistem->id) == strtolower($sistemId)));
    }

    /**
     * Prikaz podatkov o razsvetljavi cone
     *
     * @param string $projectId Building name
     * @param string $sistemId Id
     * @return void
     */
    public function razsvetljava($projectId, $sistemId)
    {
        App::set('projectId', $projectId);

        $sistemi = App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'razsvetljava');
        App::set('sistemi', $sistemi);
        App::set('sistem', array_first($sistemi, fn($sistem) => strtolower($sistem->id) == strtolower($sistemId)));
    }

    /**
     * Prikaz podatkov o sistemu ogrevanja
     *
     * @param string $projectId Building name
     * @param string $sistemId Id
     * @return void
     */
    public function ogrevanje($projectId, $sistemId)
    {
        App::set('projectId', $projectId);

        $sistemi = App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'ogrevanje');
        App::set('sistemi', $sistemi);
        App::set('sistem', array_first($sistemi, fn($sistem) => strtolower($sistem->id) == strtolower($sistemId)));
    }

    /**
     * Prikaz podatkov o sistemu fotovoltaike
     *
     * @param string $projectId Building name
     * @param string $sistemId Id
     * @return void
     */
    public function fotovoltaika($projectId, $sistemId)
    {
        App::set('projectId', $projectId);

        $sistemi = App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'fotovoltaika');
        App::set('sistemi', $sistemi);
        App::set('sistem', array_first($sistemi, fn($sistem) => strtolower($sistem->id) == strtolower($sistemId)));
    }
}
