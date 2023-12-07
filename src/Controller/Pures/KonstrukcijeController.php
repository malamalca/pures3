<?php
declare(strict_types=1);

namespace App\Controller\Pures;

use App\Core\App;
use App\Lib\CalcKonstrukcije;

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
        App::set('okolje', App::loadProjectCalculation('Pures', $projectName, 'okolje'));

        $ntKonsArray = App::loadProjectCalculation('Pures', $projectName, 'konstrukcije' . DS . 'netransparentne');
        $kons = array_first($ntKonsArray, fn($item) => $item->id == $konsId);

        if (empty($kons)) {
            throw new \Exception('Konstrukcija ne obstaja');
        }

        App::set('kons', $kons);
    }

    /**
     * Prikaz izračun U vrednosti
     *
     * @param string|null $konsId Konstruction id
     * @return void
     */
    public function u($konsId = null)
    {
        $kons = <<<EOT
        {
            "id": "F6",
            "naziv": "streha",
            "vrsta": 0,
            "Rsi": 0.13,
            "Rse": 0.04,
            "materiali": [
                {"sifra": "beton", "debelina": 0.16},
                {"sifra": "kamenaVolna", "debelina": 0.1},
                {"sifra": "XPS", "debelina": 0.03},
                {"sifra": "cementnoLepilo", "debelina": 0.002},
                {"sifra": "fasadniSloj", "debelina": 0.002}
            ]
        }
        EOT;
        App::set('kons', $kons);

        if (!empty($_POST['data'])) {
            $okolje = new \stdClass();
            $okolje->notranjaT = [22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22];
            $okolje->zunanjaT = [-10, 40, 40, 40, 40, 40, 40, 40, 40, 40, 40, 40];
            $okolje->notranjaVlaga = [50, 50, 50, 50, 50, 50, 50, 50, 50, 50, 50, 50];
            $okolje->zunanjaVlaga = [60, 60, 60, 60, 60, 60, 60, 60, 60, 60, 60, 60];
            $okolje->minfRsi = [1];

            $libraryArray = json_decode((string)file_get_contents(CONFIG . 'TSGKonstrukcije.json'));
            foreach ($libraryArray as $item) {
                CalcKonstrukcije::$library[$item->sifra] = $item;
            }

            $kons = CalcKonstrukcije::konstrukcija(json_decode($kons), $okolje, ['izracunKondenzacije' => true]);
            App::set('kons', $kons);
            App::set('okolje', $okolje);
        }
    }

    /**
     * Prikaz slike grafa
     *
     * @return void
     */
    public function graf()
    {
        $png = CalcKonstrukcije::graf($_GET);

        header('Content-Type: image/png');
        echo $png;
    }
}
