<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class ConeController
{
    /**
     * Prikaz podatkov o ovoju cone
     *
     * @param string $projectName Building name
     * @param string $conaId Id cone
     * @return void
     */
    public function ovoj($projectName, $conaId)
    {
        $cone = App::loadProjectCalculation($projectName, 'cone');

        App::set('projectName', $projectName);
        App::set('cona', array_first($cone, fn($cona) => strtolower($cona->id) == strtolower($conaId)));
    }

    /**
     * Prikaz analize cone
     *
     * @param string $projectName Building name
     * @param string $conaId Id cone
     * @return void
     */
    public function analiza($projectName, $conaId)
    {
        $cone = App::loadProjectCalculation($projectName, 'cone');

        App::set('projectName', $projectName);
        App::set('cona', array_first($cone, fn($cona) => strtolower($cona->id) == strtolower($conaId)));
    }
}
