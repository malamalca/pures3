<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class KonstrukcijeController
{
    /**
     * Prikaz podatkov o konstrukciji
     *
     * @param string $projectName Building name
     * @param string $konsId Konstruction id
     * @return void
     */
    public function view($projectName, $konsId)
    {
        App::set('okolje', App::loadProjectCalculation($projectName, 'okolje'));

        $ntKonsArray = App::loadProjectCalculation($projectName, 'konstrukcije' . DS . 'netransparentne');
        $kons = array_first($ntKonsArray, fn($item) => $item->id = $konsId);

        if (empty($kons)) {
            throw new \Exception('Konstrukcija ne obstaja');
        }

        App::set('kons', $kons);
    }
}
