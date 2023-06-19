<?php
declare(strict_types=1);

namespace App\Command;

use App\Core\App;
use App\Core\Command;
use App\Lib\Calc;
use App\Lib\CalcOkolje;
use JsonSchema\Validator;

class IzracunOkolja extends Command
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

        $splosniPodatkiIn = App::loadProjectData($projectId, 'splosniPodatki');

        $validator = new Validator();
        $schema = (string)file_get_contents(SCHEMAS . 'splosniPodatkiSchema.json');
        $validator->validate($splosniPodatkiIn, json_decode($schema));
        if (!$validator->isValid()) {
            $this->out('splosniPodatki.json vsebuje napake:', 'error');
            foreach ($validator->getErrors() as $error) {
                $this->out(sprintf('[%s] %s', $error['property'], $error['message']), 'info');
            }
        }

        $splosniPodatkiOut = $splosniPodatkiIn;

        // find temp regime
        if (empty($splosniPodatkiIn->stavba->koordinate->X) || empty($splosniPodatkiIn->stavba->koordinate->Y)) {
            throw new \Exception('Koordinate stavbe niso vpisane.');
        }

        $YXTemp = json_decode((string)file_get_contents(CONFIG . 'YXTemp.json'));
        $YXTempNearest = null;
        $nearestDistance = null;
        foreach ($YXTemp as $line) {
            $stavbaX = $splosniPodatkiIn->stavba->koordinate->X;
            $stavbaY = $splosniPodatkiIn->stavba->koordinate->Y;
            $lineDistance = sqrt(pow($line->GKY - $stavbaY, 2) + pow($line->GKX - $stavbaX, 2));

            if (is_null($nearestDistance) || ($lineDistance < $nearestDistance)) {
                $nearestDistance = $lineDistance;
                $YXTempNearest = $line;
            }
        }
        $splosniPodatkiOut->YXTemp = $YXTempNearest;

        $YXVlaga = json_decode((string)file_get_contents(CONFIG . 'YXVlaga.json'));
        $YXVlagaNearest = null;
        $nearestDistance = null;
        foreach ($YXVlaga as $line) {
            $stavbaX = $splosniPodatkiIn->stavba->koordinate->X;
            $stavbaY = $splosniPodatkiIn->stavba->koordinate->Y;
            $lineDistance = sqrt(pow($line->GKY - $stavbaY, 2) + pow($line->GKX - $stavbaX, 2));

            if (is_null($nearestDistance) || $lineDistance < $nearestDistance) {
                $nearestDistance = $lineDistance;
                $YXVlagaNearest = $line;
            }
        }
        $splosniPodatkiOut->YXVlaga = $YXVlagaNearest;

        $zunanjaTemp = [];
        $zunanjaVlaga = [];
        foreach (Calc::MESECI as $mesecId => $mesec) {
            if (isset($splosniPodatkiOut->YXTemp->$mesec)) {
                $zunanjaTemp[$mesecId] = $splosniPodatkiOut->YXTemp->$mesec;
            }
            if (isset($splosniPodatkiOut->YXVlaga->$mesec)) {
                $zunanjaVlaga[$mesecId] = $splosniPodatkiOut->YXVlaga->$mesec;
            }
        }

        $okolje = CalcOkolje::notranjeOkolje(['zunanjaT' => $zunanjaTemp, 'zunanjaVlaga' => $zunanjaVlaga]);

        $okolje->povprecnaLetnaTemp = $splosniPodatkiOut->YXTemp->letnaT;
        $okolje->projektnaZunanjaT = $splosniPodatkiOut->YXTemp->projT;
        $okolje->temperaturniPrimanjkljaj = $splosniPodatkiOut->YXTemp->tempPrim;
        $okolje->energijaSoncnegaObsevanja = $splosniPodatkiOut->YXTemp->sevanje;

        // izračun sončnega obsevanja
        // ARSO in Pures3 naredita to zelo čudno - ne na podlagi koordinat ampak primerja letno sevanje
        // iz podatkov za temperaturo in letno sevanje na horizontalno površino
        $YXObsevanje = json_decode((string)file_get_contents(CONFIG . 'YXObsevanje.json'));
        $YXObsevanjeNearest = null;
        //$nearestDistance = null;
        foreach ($YXObsevanje as $lineIndex => $line) {
            /*$stavbaX = $splosniPodatkiIn->stavba->koordinate->X;
            $stavbaY = $splosniPodatkiIn->stavba->koordinate->Y;
            $lineDistance = sqrt(pow($line->GKY - $stavbaY, 2) + pow($line->GKX - $stavbaX, 2));

            if (is_null($nearestDistance) || $lineDistance < $nearestDistance) {
                $nearestDistance = $lineDistance;
                $YXObsevanjeNearest = $line;
                $YXObsevanjeNearest->id = $lineIndex;
            }*/
            if ($YXTempNearest->sevanje == $line->podatki[0]->leto) {
                $YXObsevanjeNearest = $line;
                $YXObsevanjeNearest->id = $lineIndex;
                break;
            }
        }

        $okolje->obsevanje = $YXObsevanjeNearest->podatki;

        App::saveProjectCalculation($projectId, 'okolje', $okolje);
    }
}
