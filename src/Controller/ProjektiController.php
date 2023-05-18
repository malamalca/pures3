<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class ProjektiController
{
    /**
     * Prikaz analize projekta
     *
     * @param string $buildingName Building name
     * @return void
     */
    public function analiza($buildingName)
    {
        $splosniPodatkiFile = PROJECTS . $buildingName . DS . 'podatki' . DS . 'splosniPodatki.json';
        $splosniPodatki = json_decode(file_get_contents($splosniPodatkiFile));

        App::set('splosniPodatki', $splosniPodatki);

        $stavbaFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'stavba.json';
        $stavba = json_decode(file_get_contents($stavbaFile));

        App::set('stavba', $stavba);
    }
}
