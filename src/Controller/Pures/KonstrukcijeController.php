<?php
declare(strict_types=1);

namespace App\Controller\Pures;

use App\Core\App;
use App\Lib\CalcKonstrukcije;
use App\Lib\SpanIterators\DailySpanIterator;

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
        $data = <<<EOT
        {
            "id": "F6",
            "naziv": "fasada / mansarda / kontaktna fasada / južna stena ob ganku",
            "vrsta": 0,
            "Rsi": 0.13,
            "Rse": 0.04,
            "materiali": [
                {"sifra": "MKplosce", "debelina": 0.025},
                {"sifra": "kamenaVolna", "debelina": 0.05},
                {"sifra": "opeka", "debelina": 0.205},
                {"sifra": "kamenaVolna", "debelina": 0.16},
                {"sifra": "cementnoLepilo", "debelina": 0.005},
                {"sifra": "fasadniSloj", "debelina": 0.002}
            ]
        }
        EOT;
        App::set('data', $data);

        if (!empty($_POST['data'])) {
            $okolje = new \stdClass();
            $okolje->notranjaT = [20];
            $okolje->zunanjaT = [-13];
            $okolje->notranjaVlaga = [50];
            $okolje->zunanjaVlaga = [90];

            $libraryArray = json_decode((string)file_get_contents(CONFIG . 'TSGKonstrukcije.json'));
            foreach ($libraryArray as $item) {
                CalcKonstrukcije::$library[$item->sifra] = $item;
            }

            CalcKonstrukcije::$spanIterator = new DailySpanIterator();

            $kons = CalcKonstrukcije::konstrukcija(json_decode($data), $okolje, ['izracunKondenzacije' => false]);
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
