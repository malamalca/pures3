<?php
declare(strict_types=1);

namespace App\Controller\Hrup;

use App\Core\App;
use App\Core\Controller;

class UdarniHrupController extends Controller
{
    /**
     * Prikaz
     *
     * @param string $projectId Building name
     * @param string $konstrukcijaId Id ločilne konstrukcije
     * @return void
     */
    public function view($projectId, $konstrukcijaId)
    {
        App::set('projectId', $projectId);
        App::set('splosniPodatki', App::loadProjectData('Hrup', $projectId, 'splosniPodatki'));

        $locilneKonstrukcije = App::loadProjectCalculation('Hrup', $projectId, 'udarniHrup');
        App::set('locilnaKonstrukcija', array_first($locilneKonstrukcije, fn($p) => $konstrukcijaId == $p->id));

        App::set('konstrukcije', App::loadProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'konstrukcije'));
    }
}
