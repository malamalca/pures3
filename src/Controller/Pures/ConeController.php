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
     * @param string|null $ref Referenčna stavba
     * @return void
     */
    public function ovoj($projectId, $conaId, $ref = null)
    {
        $cone = App::loadProjectCalculation('Pures', $projectId, 'cone' . ($ref == 'ref' ? '_ref' : ''));

        App::set('projectId', $projectId);
        App::set('cona', array_first($cone, fn($cona) => strtolower($cona->id) == strtolower($conaId)));
    }

    /**
     * Prikaz analize cone
     *
     * @param string $projectId Building name
     * @param string $conaId Id cone
     * @param string|null $ref Referenčna stavba
     * @return void
     */
    public function analiza($projectId, $conaId, $ref = null)
    {
        $cone = App::loadProjectCalculation('Pures', $projectId, 'cone' . ($ref == 'ref' ? '_ref' : ''));

        App::set('projectId', $projectId);
        App::set('cona', array_first($cone, fn($cona) => strtolower($cona->id) == strtolower($conaId)));
    }

    /**
     * Prikaz transparentne konstrukcije cone
     *
     * @param string $projectId Building name
     * @param string $conaId Id cone
     * @param string $konsId Id konstrukcije
     * @return void
     */
    public function transparentniElement($projectId, $conaId, $konsId)
    {
        $cone = App::loadProjectCalculation('Pures', $projectId, 'cone');
        $cona = array_first($cone, fn($cona) => strtolower($cona->id) == strtolower($conaId));
        $k = array_first($cona->ovoj->transparentneKonstrukcije, fn($kn) => strtolower($kn->id) == strtolower($konsId));

        App::set('projectId', $projectId);
        App::set('cona', $cona);
        App::set('kons', $k);
    }
}
