<?php
declare(strict_types=1);

namespace App\Controller\Pures;

use App\Core\App;
use App\Lib\Calc;
use App\Lib\CalcKonstrukcije;
use App\Lib\CalcOkolje;

class KonstrukcijeController
{
    /**
     * Prikaz seznama
     *
     * @param string $projectId Building name
     * @return void
     */
    public function index($projectId)
    {
        $ntKonsArray = App::loadProjectCalculation('Pures', $projectId, 'konstrukcije' . DS . 'netransparentne');

        App::set('konstrukcije', $ntKonsArray);
        App::set('projectId', $projectId);
    }

    /**
     * Prikaz podatkov o konstrukciji
     *
     * @param string $projectId Building name
     * @param string $konsId Konstruction id
     * @return void
     */
    public function view($projectId, $konsId)
    {
        App::set('okolje', App::loadProjectCalculation('Pures', $projectId, 'okolje'));

        $ntKonsArray = App::loadProjectCalculation('Pures', $projectId, 'konstrukcije' . DS . 'netransparentne');
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
        $jsonKons = <<<EOT
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

        $GKY = 462000;
        $GKX = 101000;
        if (!empty($_POST['data'])) {
            $jsonKons = $_POST['data'];
            $GKY = (int)$_POST['GKY'];
            $GKX = (int)$_POST['GKX'];

            $okolje = $this->izracunOkolja($GKY, $GKX);
            //$okolje->minfRsi = [1];

            $libraryArray = json_decode((string)file_get_contents(CONFIG . 'TSGKonstrukcije.json'));
            foreach ($libraryArray as $item) {
                CalcKonstrukcije::$library[$item->sifra] = $item;
            }

            $kons = CalcKonstrukcije::konstrukcija(json_decode($jsonKons), $okolje, ['izracunKondenzacije' => true]);
            App::set('kons', json_decode((string)json_encode($kons)));
            App::set('okolje', $okolje);
        }

        App::set('GKY', $GKY);
        App::set('GKX', $GKX);
        App::set('jsonKons', $jsonKons);
    }

    /**
     * Izracun okolja
     *
     * @param int $GKY GKY
     * @param int $GKX GKX
     * @return \stdClass
     */
    private function izracunOkolja($GKY, $GKX)
    {
        $YXTemp = json_decode((string)file_get_contents(CONFIG . 'YXTemp.json'));
        $YXTempNearest = null;
        $nearestDistance = null;
        foreach ($YXTemp as $line) {
            $stavbaX = $GKX;
            $stavbaY = $GKY;
            $lineDistance = sqrt(pow($line->GKY - $stavbaY, 2) + pow($line->GKX - $stavbaX, 2));

            if (is_null($nearestDistance) || ($lineDistance < $nearestDistance)) {
                $nearestDistance = $lineDistance;
                $YXTempNearest = $line;
            }
        }

        $YXVlaga = json_decode((string)file_get_contents(CONFIG . 'YXVlaga.json'));
        $YXVlagaNearest = null;
        $nearestDistance = null;
        foreach ($YXVlaga as $line) {
            $stavbaX = $GKX;
            $stavbaY = $GKY;
            $lineDistance = sqrt(pow($line->GKY - $stavbaY, 2) + pow($line->GKX - $stavbaX, 2));

            if (is_null($nearestDistance) || $lineDistance < $nearestDistance) {
                $nearestDistance = $lineDistance;
                $YXVlagaNearest = $line;
            }
        }

        $zunanjaTemp = [];
        $zunanjaVlaga = [];
        foreach (Calc::MESECI as $mesecId => $mesec) {
            if (isset($YXTempNearest->$mesec)) {
                $zunanjaTemp[$mesecId] = $YXTempNearest->$mesec;
            }
            if (isset($YXVlagaNearest->$mesec)) {
                $zunanjaVlaga[$mesecId] = $YXVlagaNearest->$mesec;
            }
        }

        $okolje = CalcOkolje::notranjeOkolje(['zunanjaT' => $zunanjaTemp, 'zunanjaVlaga' => $zunanjaVlaga]);

        return $okolje;
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
