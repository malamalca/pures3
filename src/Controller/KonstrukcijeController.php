<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class KonstrukcijeController
{
    /**
     * Prikaz podatkov o konstrukciji
     *
     * @param string $buildingName Building name
     * @param string $konsId Konstruction id
     * @return void
     */
    public function view($buildingName, $konsId)
    {
        $ntKonsFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'konstrukcije' . DS . 'netransparentne.json';
        $ntKonsArray = json_decode(file_get_contents($ntKonsFile));

        $okoljeFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'okolje.json';
        $okolje = json_decode(file_get_contents($okoljeFile));

        $kons = null;
        foreach ($ntKonsArray as $aKons) {
            if ($aKons->id == $konsId) {
                $kons = $aKons;
            }
        }

        if (empty($kons)) {
            die('Konstrukcija ne obstaja');
        }

        App::set('kons', $kons);
        App::set('okolje', $okolje);
    }
}
