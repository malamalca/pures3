<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class ConeController
{
    /**
     * Prikaz podatkov o ovoju cone
     *
     * @param string $buildingName Building name
     * @param string $conaId Id cone
     * @return void
     */
    public function ovoj($buildingName, $conaId)
    {
        $ntKonsFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'konstrukcije' . DS . 'netransparentne.json';
        $ntKonsArray = json_decode(file_get_contents($ntKonsFile));

        $tKonsFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'konstrukcije' . DS . 'transparentne.json';
        $tKonsArray = json_decode(file_get_contents($tKonsFile));

        $okoljeFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'okolje.json';
        $okolje = json_decode(file_get_contents($okoljeFile));

        $coneFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'cone.json';
        $cone = json_decode(file_get_contents($coneFile));

        $cona = null;
        foreach ($cone as $aCona) {
            if (strtolower($aCona->id) == strtolower($conaId)) {
                $cona = $aCona;
            }
        }

        if (empty($cona)) {
            die('Cona ne obstaja');
        }

        App::set('cona', $cona);
        App::set('okolje', $okolje);
    }

    /**
     * Prikaz analize cone
     *
     * @param string $buildingName Building name
     * @param string $conaId Id cone
     * @return void
     */
    public function analiza($buildingName, $conaId)
    {
        $okoljeFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'okolje.json';
        $okolje = json_decode(file_get_contents($okoljeFile));

        $coneFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'cone.json';
        $cone = json_decode(file_get_contents($coneFile));

        $cona = null;
        foreach ($cone as $aCona) {
            if (strtolower($aCona->id) == strtolower($conaId)) {
                $cona = $aCona;
            }
        }

        if (empty($cona)) {
            die('Cona ne obstaja');
        }

        App::set('cona', $cona);
        App::set('okolje', $okolje);
    }
}
