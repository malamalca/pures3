<?php
declare(strict_types=1);

namespace App\Controller\Pures;

use App\Core\App;

class ConeController
{
    /**
     * Prikaz podatkov o ovoju cone
     *
     * @param string $projectId Building name
     * @param string $conaId Id cone
     * @return void
     */
    public function ovoj($projectId, $conaId)
    {
        $cone = App::loadProjectCalculation('Pures', $projectId, 'cone');

        App::set('projectId', $projectId);
        App::set('cona', array_first($cone, fn($cona) => strtolower($cona->id) == strtolower($conaId)));
    }

    /**
     * Prikaz analize cone
     *
     * @param string $projectId Building name
     * @param string $conaId Id cone
     * @return void
     */
    public function analiza($projectId, $conaId)
    {
        $cone = App::loadProjectCalculation('Pures', $projectId, 'cone');

        App::set('projectId', $projectId);
        App::set('cona', array_first($cone, fn($cona) => strtolower($cona->id) == strtolower($conaId)));
    }
}
