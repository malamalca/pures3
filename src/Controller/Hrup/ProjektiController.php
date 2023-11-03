<?php
declare(strict_types=1);

namespace App\Controller\Hrup;

use App\Core\App;

class ProjektiController
{
    /**
     * Prikaz seznama projektov
     *
     * @return void
     */
    public function index()
    {
        $baseDir = PROJECTS . 'Hrup' . DS;
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
        App::set('splosniPodatki', App::loadProjectData('Hrup', $projectId, 'splosniPodatki'));
        App::set('prostori', App::loadProjectCalculation('Hrup', $projectId, 'zunanjiHrup'));
    }

    /**
     * Prikaz tehničnega poročila projekta
     *
     * @param string|null $projectId Building name
     * @return void
     */
    public function porocilo($projectId = null)
    {
        App::set('projectId', $projectId);
        App::set('splosniPodatki', App::loadProjectData('Hrup', $projectId, 'splosniPodatki'));
        App::set('prostori', App::loadProjectCalculation('Hrup', $projectId, 'zunanjiHrup'));
        App::set('konstrukcije', App::loadProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'konstrukcije'));
        App::set('oknaVrata', App::loadProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'oknaVrata'));
    }

    /**
     * Prikaz izkaza projekta
     *
     * @param string|null $projectId Building name
     * @return void
     */
    public function izkaz($projectId = null)
    {
        App::set('projectId', $projectId);
        App::set('splosniPodatki', App::loadProjectData('Hrup', $projectId, 'splosniPodatki'));
        App::set('prostori', App::loadProjectCalculation('Hrup', $projectId, 'zunanjiHrup'));
        App::set('konstrukcije', App::loadProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'konstrukcije'));
        App::set('oknaVrata', App::loadProjectCalculation('Hrup', $projectId, 'elementi' . DS . 'oknaVrata'));
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
        App::set('splosniPodatki', App::loadProjectData('Hrup', $projectId, 'splosniPodatki'));
    }

    /**
     * Prikaz izjave
     *
     * @param string $projectId Building name
     * @return void
     */
    public function izjava($projectId)
    {
        App::set('projectId', $projectId);
        App::set('splosniPodatki', App::loadProjectData('Hrup', $projectId, 'splosniPodatki'));
    }
}
