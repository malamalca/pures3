<?php
declare(strict_types=1);

namespace App\Command;

use App\Core\Command;
use App\Lib\CalcTSSOgrevanje;
use App\Lib\CalcTSSPrezracevanje;

class IzracunTSS extends Command
{
    /**
     * Command run routine
     *
     * @param string $projectId Project id.
     * @return void
     */
    public function run($projectId)
    {
        parent::run($projectId);

        $splosniPodatkiFile = PROJECTS . $projectId . DS . 'podatki' . DS . 'splosniPodatki.json';
        if (!file_exists($splosniPodatkiFile)) {
            throw \Exception(sprintf('Datoteka "%s" s splošnimi podatki ne obstaja.', $splosniPodatkiFile));
        }
        $splosniPodatki = json_decode(file_get_contents($splosniPodatkiFile));

        $okoljeFile = PROJECTS . $projectId . DS . 'izracuni' . DS . 'okolje.json';
        if (!file_exists($okoljeFile)) {
            throw \Exception(sprintf('Datoteka "%s" z okoljskimi podatki ne obstaja.', $okoljeFile));
        }
        $okolje = json_decode(file_get_contents($okoljeFile));

        $coneFile = PROJECTS . $projectId . DS . 'izracuni' . DS . 'cone.json';
        if (!file_exists($coneFile)) {
            throw \Exception(sprintf('Datoteka "%s" s conami ne obstaja.', $coneFile));
        }
        $cone = json_decode(file_get_contents($coneFile));
        $cone = array_combine(array_map(fn($k) => $k->id, $cone), $cone);

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $TSSPrezracevanjeFile = PROJECTS . $projectId . DS . 'podatki' . DS . 'TSS' . DS . 'prezracevanje.json';
        if (file_exists($TSSPrezracevanjeFile)) {
            $TSSSistemiPrezracevanja = json_decode(file_get_contents($TSSPrezracevanjeFile));

            $TSSSistemiPrezracevanjaOut = [];
            foreach ($TSSSistemiPrezracevanja as $sistem) {
                if (!isset($cone[$sistem->idCone])) {
                    throw \Exception('TSS Prezračevanje: Cona ne obstaja.');
                }
                $TSSSistemiPrezracevanjaOut[] = CalcTSSPrezracevanje::analiza($sistem, $cone[$sistem->idCone], $okolje, $splosniPodatki);
            }

            $TSSPrezracevanjeOutFile = PROJECTS . $projectId . DS . 'izracuni' . DS . 'TSS' . DS . 'prezracevanje.json';
            file_put_contents(
                $TSSPrezracevanjeOutFile,
                json_encode($TSSSistemiPrezracevanjaOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $TSSOgrevanjeFile = PROJECTS . $projectId . DS . 'podatki' . DS . 'TSS' . DS . 'ogrevanje.json';
        if (file_exists($TSSOgrevanjeFile)) {
            $TSSSistemiOgrevanje = json_decode(file_get_contents($TSSOgrevanjeFile));

            $TSSSistemiOgrevanjeOut = [];
            foreach ($TSSSistemiOgrevanje as $sistem) {
                if (!isset($cone[$sistem->idCone])) {
                    throw \Exception('TSS Ogrevanje: Cona ne obstaja.');
                }
                $TSSSistemiOgrevanjeOut[] = CalcTSSOgrevanje::analiza($sistem, $cone[$sistem->idCone], $okolje, $splosniPodatki);
            }

            $TSSOgrevanjeOutFile = PROJECTS . $projectId . DS . 'izracuni' . DS . 'TSS' . DS . 'ogrevanje.json';
            file_put_contents(
                $TSSOgrevanjeOutFile,
                json_encode($TSSSistemiOgrevanjeOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }
    }
}
