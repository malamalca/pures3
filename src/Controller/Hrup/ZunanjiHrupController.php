<?php
declare(strict_types=1);

namespace App\Controller\Hrup;

use App\Core\App;

class ZunanjiHrupController
{
    /**
     * Prikaz splosnih podatkov projekta
     *
     * @param string $projectId Building name
     * @param string $prostorId Id prostora
     * @return void
     */
    public function view($projectId, $prostorId)
    {
        App::set('projectId', $projectId);
        App::set('splosniPodatki', App::loadProjectData('Hrup', $projectId, 'splosniPodatki'));

        $prostori = App::loadProjectCalculation('Hrup', $projectId, 'zunanjiHrup');
        App::set('prostor', array_first($prostori, fn($p) => $prostorId == $p->id));

        App::set('konstrukcije', App::loadProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'konstrukcije'));
        App::set('oknaVrata', App::loadProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'oknaVrata'));
    }
}
