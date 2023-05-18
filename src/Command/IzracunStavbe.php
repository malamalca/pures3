<?php
declare(strict_types=1);

namespace App\Command;

use App\Core\Command;
use App\Lib\CalcStavba;

class IzracunStavbe extends Command
{
    /**
     * Command run routine
     *
     * @param string $projectId Project id.
     * @return void
     */
    public function run($projectId)
    {
        parent::run($projectId);

        $splosniPodatkiFile = PROJECTS . $projectId . DS . 'podatki' . DS . 'splosniPodatki.json';
        if (!file_exists($splosniPodatkiFile)) {
            throw \Exception(sprintf('Datoteka "%s" s splošnimi podatki ne obstaja.', $splosniPodatkiFile));
        }
        $splosniPodatki = json_decode(file_get_contents($splosniPodatkiFile));

        $okoljeFile = PROJECTS . $projectId . DS . 'izracuni' . DS . 'okolje.json';
        if (!file_exists($okoljeFile)) {
            throw \Exception(sprintf('Datoteka "%s" z okoljskimi podatki ne obstaja.', $okoljeFile));
        }
        $okolje = json_decode(file_get_contents($okoljeFile));

        $coneFile = PROJECTS . $projectId . DS . 'izracuni' . DS . 'cone.json';
        if (!file_exists($coneFile)) {
            throw \Exception(sprintf('Datoteka "%s" s conami ne obstaja.', $coneFile));
        }
        $cone = json_decode(file_get_contents($coneFile));

        $stavba = CalcStavba::analiza($cone, $okolje, $splosniPodatki);

        $stavbaOutputFile = PROJECTS . $projectId . DS . 'izracuni' . DS . 'stavba.json';
        file_put_contents($stavbaOutputFile, json_encode($stavba, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
