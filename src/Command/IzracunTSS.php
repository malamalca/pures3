<?php
declare(strict_types=1);

namespace App\Command;

use App\Calc\TSS\SistemOgrevanjaFactory;
use App\Core\Command;
use App\Lib\CalcTSSPrezracevanje;
use App\Lib\CalcTSSRazsvetljava;

class IzracunTSS extends Command
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

        $splosniPodatkiFile = PROJECTS . $projectId . DS . 'podatki' . DS . 'splosniPodatki.json';
        if (!file_exists($splosniPodatkiFile)) {
            throw new \Exception(sprintf('Datoteka "%s" s splošnimi podatki ne obstaja.', $splosniPodatkiFile));
        }
        $splosniPodatki = json_decode(file_get_contents($splosniPodatkiFile));

        $okoljeFile = PROJECTS . $projectId . DS . 'izracuni' . DS . 'okolje.json';
        if (!file_exists($okoljeFile)) {
            throw new \Exception(sprintf('Datoteka "%s" z okoljskimi podatki ne obstaja.', $okoljeFile));
        }
        $okolje = json_decode(file_get_contents($okoljeFile));

        $coneFile = PROJECTS . $projectId . DS . 'izracuni' . DS . 'cone.json';
        if (!file_exists($coneFile)) {
            throw new \Exception(sprintf('Datoteka "%s" s conami ne obstaja.', $coneFile));
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
                    throw new \Exception('TSS Prezračevanje: Cona ne obstaja.');
                }
                $TSSSistemiPrezracevanjaOut[] =
                    CalcTSSPrezracevanje::analiza($sistem, $cone[$sistem->idCone], $okolje, $splosniPodatki);
            }

            $TSSPrezracevanjeOutFile = PROJECTS . $projectId . DS . 'izracuni' . DS . 'TSS' . DS . 'prezracevanje.json';
            file_put_contents(
                $TSSPrezracevanjeOutFile,
                json_encode($TSSSistemiPrezracevanjaOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $TSSRazsvetljavaFile = PROJECTS . $projectId . DS . 'podatki' . DS . 'TSS' . DS . 'razsvetljava.json';
        if (file_exists($TSSRazsvetljavaFile)) {
            $TSSSistemiRazsvetljava = json_decode(file_get_contents($TSSRazsvetljavaFile));

            $TSSSistemiRazsvetljavaOut = [];
            foreach ($TSSSistemiRazsvetljava as $sistem) {
                if (!isset($cone[$sistem->idCone])) {
                    throw new \Exception('TSS Prezračevanje: Cona ne obstaja.');
                }
                $TSSSistemiRazsvetljavaOut[] =
                    CalcTSSRazsvetljava::analiza($sistem, $cone[$sistem->idCone], $okolje, $splosniPodatki);
            }

            $TSSRazsvetljavaOutFile = PROJECTS . $projectId . DS . 'izracuni' . DS . 'TSS' . DS . 'razsvetljava.json';
            file_put_contents(
                $TSSRazsvetljavaOutFile,
                json_encode($TSSSistemiRazsvetljavaOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $TSSOgrevanjeFile = PROJECTS . $projectId . DS . 'podatki' . DS . 'TSS' . DS . 'ogrevanje.json';
        if (file_exists($TSSOgrevanjeFile)) {
            $TSSSistemiOgrevanje = json_decode(file_get_contents($TSSOgrevanjeFile));

            $TSSSistemiOgrevanjeOut = [];
            foreach ($TSSSistemiOgrevanje as $sistem) {
                if (!isset($cone[$sistem->idCone])) {
                    throw new \Exception('TSS Ogrevanje: Cona ne obstaja.');
                }

                $sistemOgrevanja = SistemOgrevanjaFactory::create($sistem->vrsta, $sistem);
                $sistemOgrevanja->analiza($cone[$sistem->idCone], $okolje);

                foreach ($sistem->prenosniki as $k => $prenosnik) {
                    $prenosnik->toplotneIzgube = $sistemOgrevanja->koncniPrenosniki[$k]->toplotneIzgube;
                    $prenosnik->potrebnaElektricnaEnergija =
                        $sistemOgrevanja->koncniPrenosniki[$k]->potrebnaElektricnaEnergija;
                    $prenosnik->vracljiveIzgubeAux = $sistemOgrevanja->koncniPrenosniki[$k]->vracljiveIzgubeAux;
                }

                foreach ($sistem->razvodi as $k => $razvod) {
                    $razvod->toplotneIzgube = $sistemOgrevanja->razvodi[$k]->toplotneIzgube;
                    $razvod->vracljiveIzgube = $sistemOgrevanja->razvodi[$k]->vracljiveIzgube;
                    $razvod->potrebnaElektricnaEnergija = $sistemOgrevanja->razvodi[$k]->potrebnaElektricnaEnergija;
                    $razvod->vracljiveIzgubeAux = $sistemOgrevanja->razvodi[$k]->vracljiveIzgubeAux;
                }

                foreach ($sistem->hranilniki as $k => $hranilnik) {
                    $hranilnik->toplotneIzgube = $sistemOgrevanja->hranilniki[$k]->toplotneIzgube;
                }

                foreach ($sistem->generatorji as $k => $generator) {
                    $generator->potrebnaEnergija = $sistemOgrevanja->generatorji[$k]->potrebnaEnergija;
                    $generator->potrebnaElektricnaEnergija =
                        $sistemOgrevanja->generatorji[$k]->potrebnaElektricnaEnergija;
                }

                $sistem->potrebnaEnergija = $sistemOgrevanja->potrebnaEnergija;
                $sistem->potrebnaElektricnaEnergija = $sistemOgrevanja->potrebnaElektricnaEnergija;
                $sistem->obnovljivaEnergija = $sistemOgrevanja->obnovljivaEnergija;
                $sistem->vracljiveIzgube = $sistemOgrevanja->vracljiveIzgube;

                $TSSSistemiOgrevanjeOut[] = $sistem;
            }

            $TSSOgrevanjeOutDir = PROJECTS . $projectId . DS . 'izracuni' . DS . 'TSS' . DS;
            if (!is_dir($TSSOgrevanjeOutDir)) {
                mkdir($TSSOgrevanjeOutDir, 0777, true);
            }
            $TSSOgrevanjeOutFile = $TSSOgrevanjeOutDir . 'ogrevanje.json';
            file_put_contents(
                $TSSOgrevanjeOutFile,
                json_encode($TSSSistemiOgrevanjeOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }
    }
}
