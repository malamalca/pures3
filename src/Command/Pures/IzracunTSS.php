<?php
declare(strict_types=1);

namespace App\Command\Pures;

use App\Calc\GF\TSS\FotonapetostniSistemi\FotonapetostniSistem;
use App\Calc\GF\TSS\Razsvetljava\Razsvetljava;
use App\Calc\GF\TSS\SistemOgrevanjaFactory;
use App\Calc\GF\TSS\SistemPrezracevanjaFactory;
use App\Core\App;
use App\Core\Command;

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

        /** @var \stdClass $splosniPodatki */
        $splosniPodatki = App::loadProjectData('Pures', $projectId, 'splosniPodatki');

        /** @var \stdClass $okolje */
        $okolje = App::loadProjectCalculation('Pures', $projectId, 'okolje');

        /** @var array $cone */
        $cone = App::loadProjectCalculation('Pures', $projectId, 'cone');

        $elektrikaPoConah = [];

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $TSSSistemiPrezracevanja = App::loadProjectData('Pures', $projectId, 'TSS' . DS . 'prezracevanje') ?? [];
        if (count($TSSSistemiPrezracevanja) > 0) {
            $TSSSistemiPrezracevanjaOut = [];
            foreach ($TSSSistemiPrezracevanja as $sistem) {
                $cona = array_first($cone, fn($cona) => $cona->id == $sistem->idCone);
                if (!$cona) {
                    throw new \Exception(sprintf('TSS Prezračevanje: Cona "%s" ne obstaja.', $sistem->idCone));
                }
                $prezracevalniSistem = SistemPrezracevanjaFactory::create($sistem->vrsta, $sistem);
                $prezracevalniSistem->analiza([], $cona, $okolje);

                $elektrikaPoConah[$sistem->idCone] =
                    array_sum_values($elektrikaPoConah[$sistem->idCone], $prezracevalniSistem->potrebnaEnergija);
                $TSSSistemiPrezracevanjaOut[] = $prezracevalniSistem->export();
            }
            App::saveProjectCalculation('Pures', $projectId, 'TSS' . DS . 'prezracevanje', $TSSSistemiPrezracevanjaOut);
        }

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $TSSSistemiRazsvetljava = App::loadProjectData('Pures', $projectId, 'TSS' . DS . 'razsvetljava') ?? [];
        if (count($TSSSistemiRazsvetljava) > 0) {
            $TSSSistemiRazsvetljavaOut = [];
            foreach ($TSSSistemiRazsvetljava as $sistem) {
                $cona = array_first($cone, fn($cona) => $cona->id == $sistem->idCone);
                if (!$cona) {
                    throw new \Exception('TSS Razsvetljava: Cona ne obstaja.');
                }
                $razsvetljava = new Razsvetljava($sistem);
                $razsvetljava->analiza([], $cona, $okolje);

                $elektrikaPoConah[$sistem->idCone] =
                    array_sum_values($elektrikaPoConah[$sistem->idCone], $razsvetljava->potrebnaEnergija);
                $TSSSistemiRazsvetljavaOut[] = $razsvetljava->export();
            }
            App::saveProjectCalculation('Pures', $projectId, 'TSS' . DS . 'razsvetljava', $TSSSistemiRazsvetljavaOut);
        }

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $TSSSistemiOgrevanje = App::loadProjectData('Pures', $projectId, 'TSS' . DS . 'ogrevanje') ?? [];
        if (count($TSSSistemiOgrevanje) > 0) {
            $TSSSistemiOgrevanjeOut = [];
            $vracljiveIzgubeVOgrevanje = [];
            foreach ($TSSSistemiOgrevanje as $sistem) {
                $cona = array_first($cone, fn($cona) => $cona->id == $sistem->idCone);
                if (!$cona) {
                    throw new \Exception('TSS Ogrevanje: Cona ne obstaja.');
                }

                $sistemOgrevanja = SistemOgrevanjaFactory::create($sistem->vrsta, $sistem);
                if (!$sistemOgrevanja) {
                    throw new \Exception(sprintf('TSS Ogrevanje: Sistem "%s" ne obstaja.', $sistem->id));
                }

                $sistemOgrevanja->vracljiveIzgubeVOgrevanje = $vracljiveIzgubeVOgrevanje;
                $sistemOgrevanja->analiza($cona, $okolje);

                $vracljiveIzgubeVOgrevanje = $sistemOgrevanja->vracljiveIzgubeVOgrevanje;

                $elektrikaPoConah[$sistemOgrevanja->idCone] = array_sum_values(
                    $elektrikaPoConah[$sistemOgrevanja->idCone],
                    $sistemOgrevanja->potrebnaElektricnaEnergija
                );
                $elektrikaPoConah[$sistemOgrevanja->idCone] =
                    array_sum_values(
                        $elektrikaPoConah[$sistemOgrevanja->idCone],
                        array_subtract_values(
                            $sistemOgrevanja->potrebnaEnergija,
                            $sistemOgrevanja->obnovljivaEnergija
                        )
                    );

                $TSSSistemiOgrevanjeOut[] = $sistemOgrevanja->export();
            }

            App::saveProjectCalculation('Pures', $projectId, 'TSS' . DS . 'ogrevanje', $TSSSistemiOgrevanjeOut);
        }

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $TSSFotonapetostniSistemi = App::loadProjectData('Pures', $projectId, 'TSS' . DS . 'fotovoltaika') ?? [];
        if (count($TSSFotonapetostniSistemi) > 0) {
            $TSSFotonapetostniSistemiOut = [];
            foreach ($TSSFotonapetostniSistemi as $sistem) {
                $cona = array_first($cone, fn($cona) => $cona->id == $sistem->idCone);
                if (!$cona) {
                    throw new \Exception('TSS Fotovoltaika: Cona ne obstaja.');
                }

                $fotonapetostniSistem = new FotonapetostniSistem($sistem);
                $fotonapetostniSistem->analiza(
                    $elektrikaPoConah[$sistem->idCone],
                    $cona,
                    $okolje
                );

                $sistem->energijaPoEnergentih = $fotonapetostniSistem->energijaPoEnergentih;

                $TSSFotonapetostniSistemiOut[] = $fotonapetostniSistem;
            }
            App::saveProjectCalculation('Pures', $projectId, 'TSS' . DS . 'fotovoltaika', $TSSFotonapetostniSistemiOut);
        }
    }
}
