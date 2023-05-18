<?php
declare(strict_types=1);

namespace App\Command;

use App\Core\Command;
use App\Lib\CalcKonstrukcije;

class IzracunKonstrukcij extends Command
{
    /**
     * Command run routine
     *
     * @param string|null $projectId Project id.
     * @return void
     */
    public function run($projectId = null)
    {
        parent::run();

        $okoljeFile = PROJECTS . $projectId . DS . 'izracuni' . DS . 'okolje.json';
        if (!file_exists($okoljeFile)) {
            throw new \Exception(sprintf('Datoteka "%s" z okoljem podatki ne obstaja.', $okoljeFile));
        }
        $okolje = json_decode(file_get_contents($okoljeFile));

        $ntKonsInputFile = PROJECTS . $projectId . DS . 'podatki' . DS . 'konstrukcije' . DS . 'netransparentne.json';
        $ntKonsIn = json_decode(file_get_contents($ntKonsInputFile));

        $ntKonsOut = [];
        foreach ($ntKonsIn as $konstrukcija) {
            $ntKonsOut[] = CalcKonstrukcije::konstrukcija($konstrukcija, $okolje);
        }

        $ntKonsOutputFolder = PROJECTS . $projectId . DS . 'izracuni' . DS . 'konstrukcije' . DS;
        $ntKonsOutputFile = 'netransparentne.json';
        if (!is_dir($ntKonsOutputFolder)) {
            mkdir($ntKonsOutputFolder, 0777, true);
        }
        file_put_contents(
            $ntKonsOutputFolder . $ntKonsOutputFile,
            json_encode($ntKonsOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // transparentne konstrukcije
        $tKonsInputFile = PROJECTS . $projectId . DS . 'podatki' . DS . 'konstrukcije' . DS . 'transparentne.json';
        $tKonsIn = json_decode(file_get_contents($tKonsInputFile));

        $tKonsOut = [];
        foreach ($tKonsIn as $konstrukcija) {
            $tKonsOut[] = CalcKonstrukcije::transparentne($konstrukcija, $okolje);
        }

        $tKonsOutputFolder = PROJECTS . $projectId . DS . 'izracuni' . DS . 'konstrukcije' . DS;
        $tKonsOutputFile = 'transparentne.json';
        if (!is_dir($tKonsOutputFolder)) {
            mkdir($tKonsOutputFolder, 0777, true);
        }
        file_put_contents(
            $tKonsOutputFolder . $tKonsOutputFile,
            json_encode($tKonsOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
