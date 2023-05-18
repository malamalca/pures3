<?php
declare(strict_types=1);

namespace App\Command;

use App\Core\Command;
use App\Lib\CalcCone;

class IzracunCone extends Command
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
            throw new \Exception(sprintf('Datoteka "%s" z okoljskimi podatki ne obstaja.', $okoljeFile));
        }
        $okolje = json_decode(file_get_contents($okoljeFile));

        $netransparentneKonstrukcije = [];
        $netransparentneFile = PROJECTS . $projectId . DS . 'izracuni' . DS .
            'konstrukcije' . DS . 'netransparentne.json';
        if (file_exists($netransparentneFile)) {
            $netransparentneKonstrukcije = json_decode(file_get_contents($netransparentneFile));
        }

        $transparentneKonstrukcije = [];
        $transparentneFile = PROJECTS . $projectId . DS . 'izracuni' . DS .
            'konstrukcije' . DS . 'transparentne.json';
        if (file_exists($transparentneFile)) {
            $transparentneKonstrukcije = json_decode(file_get_contents($transparentneFile));
        }

        $coneInputFile = PROJECTS . $projectId . DS . 'podatki' . DS . 'cone.json';
        $coneIn = json_decode(file_get_contents($coneInputFile));

        $coneOut = [];
        foreach ($coneIn as $cona) {
            $coneOut[] = CalcCone::analizaCone(
                $cona,
                $okolje,
                $netransparentneKonstrukcije,
                $transparentneKonstrukcije
            );
        }

        if (count($coneOut) == 0) {
            throw new \Exception('Cone ne obstajajo.');
        }

        $coneOutputFile = PROJECTS . $projectId . DS . 'izracuni' . DS . 'cone.json';
        file_put_contents($coneOutputFile, json_encode($coneOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
