<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class TSSController
{
    /**
     * Prikaz podatkov o ovoju cone
     *
     * @param string $buildingName Building name
     * @param string $sistemId Id
     * @return void
     */
    public function prezracevanje($buildingName, $sistemId)
    {
        $prezracevanjeFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'TSS' . DS . 'prezracevanje.json';
        $sistemi = json_decode(file_get_contents($prezracevanjeFile));

        $sistem = null;
        foreach ($sistemi as $aSistem) {
            if (strtolower($aSistem->id) == strtolower($sistemId)) {
                $sistem = $aSistem;
            }
        }

        if (empty($sistem)) {
            die('Sistem ne obstaja');
        }

        App::set('sistem', $sistem);
    }

    /**
     * Prikaz podatkov o razsvetljavi cone
     *
     * @param string $buildingName Building name
     * @param string $sistemId Id
     * @return void
     */
    public function razsvetljava($buildingName, $sistemId)
    {
        $razsvetljavaFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'TSS' . DS . 'razsvetljava.json';
        $sistemi = json_decode(file_get_contents($razsvetljavaFile));

        $sistem = null;
        foreach ($sistemi as $aSistem) {
            if (strtolower($aSistem->id) == strtolower($sistemId)) {
                $sistem = $aSistem;
            }
        }

        if (empty($sistem)) {
            die('Sistem ne obstaja');
        }

        App::set('sistem', $sistem);
    }

    /**
     * Prikaz podatkov o sistemu ogrevanja
     *
     * @param string $buildingName Building name
     * @param string|null $sistemId Id
     * @return void
     */
    public function ogrevanje($buildingName, $sistemId = null)
    {
        $ogrevanjeFile = PROJECTS . $buildingName . DS . 'izracuni' . DS . 'TSS' . DS . 'ogrevanje.json';
        $sistemi = json_decode(file_get_contents($ogrevanjeFile));

        $sistem = null;
        foreach ($sistemi as $aSistem) {
            if (strtolower($aSistem->id) == strtolower($sistemId)) {
                $sistem = $aSistem;
            }
        }

        if (empty($sistem)) {
            die('Sistem ne obstaja');
        }

        App::set('sistem', $sistem);
    }
}
