<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class TSSController
{
    /**
     * Prikaz podatkov o ovoju cone
     *
     * @param string $projectName Building name
     * @param string $sistemId Id
     * @return void
     */
    public function prezracevanje($projectName, $sistemId)
    {
        App::set('projectName', $projectName);

        $sistemi = App::loadProjectCalculation($projectName, 'TSS' . DS . 'prezracevanje');
        App::set('sistem', array_first($sistemi, fn($sistem) => strtolower($sistem->id) == strtolower($sistemId)));
    }

    /**
     * Prikaz podatkov o razsvetljavi cone
     *
     * @param string $projectName Building name
     * @param string $sistemId Id
     * @return void
     */
    public function razsvetljava($projectName, $sistemId)
    {
        App::set('projectName', $projectName);

        $sistemi = App::loadProjectCalculation($projectName, 'TSS' . DS . 'razsvetljava');
        App::set('sistem', array_first($sistemi, fn($sistem) => strtolower($sistem->id) == strtolower($sistemId)));
    }

    /**
     * Prikaz podatkov o sistemu ogrevanja
     *
     * @param string $projectName Building name
     * @param string $sistemId Id
     * @return void
     */
    public function ogrevanje($projectName, $sistemId)
    {
        App::set('projectName', $projectName);

        $sistemi = App::loadProjectCalculation($projectName, 'TSS' . DS . 'ogrevanje');
        App::set('sistem', array_first($sistemi, fn($sistem) => strtolower($sistem->id) == strtolower($sistemId)));
    }
}
