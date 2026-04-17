<?php
declare(strict_types=1);

namespace App\Command\Pures;

use App\Calc\GF\Cone\Cona;
use App\Calc\GF\TSS\FotonapetostniSistemi\FotonapetostniSistem;
use App\Calc\GF\TSS\Razsvetljava\Razsvetljava;
use App\Calc\GF\TSS\SistemOHTFactory;
use App\Calc\GF\TSS\SistemPrezracevanjaFactory;
use App\Core\App;
use App\Core\Command;

class IzracunTSS extends Command
{
    /**
     * Command run routine
     *
     * @param string|null $projectId Project id.
     * @param array|null $args Additional arguments
     * @return void
     */
    public function run($projectId = null, ...$args)
    {
        parent::run();

        /** @var \stdClass $splosniPodatki */
        $splosniPodatki = App::loadProjectData('Pures', $projectId, 'splosniPodatki');

        /** @var \stdClass $okolje */
        $okolje = App::loadProjectCalculation('Pures', $projectId, 'okolje');

        /** @var array $cone */
        $cone = App::loadProjectCalculation('Pures', $projectId, 'cone');

        $referencneCone = [];
        if ($splosniPodatki->stavba->vrsta == 'zahtevna') {
            /** @var array $cone */
            $referencneCone = App::loadProjectCalculation('Pures', $projectId, 'Ref' . DS . 'cone');
        }

        $elektrikaPoConah = [];
        $elektrikaPoReferencnihConah = [];

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $TSSSistemiPrezracevanja = App::loadProjectData('Pures', $projectId, 'TSS' . DS . 'prezracevanje') ?? [];
        if (count($TSSSistemiPrezracevanja) > 0) {
            $TSSSistemiPrezracevanjaOut = [];
            foreach ($TSSSistemiPrezracevanja as $sistem) {
                $cona = array_first_callback($cone, fn($cona) => $cona->id == $sistem->idCone);
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

            // za referenčno stavbo
            if ($splosniPodatki->stavba->vrsta == 'zahtevna') {
                $TSSReferencniSistemiPrezracevanjaOut = [];
                foreach ($referencneCone as $cona) {
                    $conaClass = new Cona(null, $cona);
                    $refSistemi = $conaClass->referencniTSS('prezracevanje');
                    foreach ($refSistemi as $refSistem) {
                        $referencniPrezracevalniSistem =
                            SistemPrezracevanjaFactory::create($refSistem->vrsta, $refSistem, true);
                        $referencniPrezracevalniSistem->analiza([], $cona, $okolje);

                        $elektrikaPoReferencnihConah[$refSistem->idCone] = array_sum_values(
                            $elektrikaPoReferencnihConah[$refSistem->idCone],
                            $referencniPrezracevalniSistem->potrebnaEnergija
                        );

                        $TSSReferencniSistemiPrezracevanjaOut[] = $referencniPrezracevalniSistem->export();
                    }
                }
                App::saveProjectCalculation(
                    'Pures',
                    $projectId,
                    'Ref' . DS . 'TSS' . DS . 'prezracevanje',
                    $TSSReferencniSistemiPrezracevanjaOut
                );
            }
        }

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $TSSSistemiRazsvetljava = App::loadProjectData('Pures', $projectId, 'TSS' . DS . 'razsvetljava') ?? [];
        if (count($TSSSistemiRazsvetljava) > 0) {
            if (!$this->validateSchema(json: $TSSSistemiRazsvetljava, schema: 'razsvetljava', area: 'Pures')) {
                throw new \Exception('Napake v opisu TSS Razsvetljava.');
            }
            $TSSSistemiRazsvetljavaOut = [];
            foreach ($TSSSistemiRazsvetljava as $sistem) {
                $cona = array_first_callback($cone, fn($cona) => $cona->id == $sistem->idCone);
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

            // za referenčno stavbo
            if ($splosniPodatki->stavba->vrsta == 'zahtevna') {
                $TSSReferencniSistemiRazsvetljavaOut = [];
                foreach ($referencneCone as $cona) {
                    $conaClass = new Cona(null, $cona);
                    $refSistemi = $conaClass->referencniTSS('razsvetljava');
                    foreach ($refSistemi as $refSistem) {
                        $referencnaRazsvetljava = new Razsvetljava($refSistem, true);
                        $referencnaRazsvetljava->analiza([], $cona, $okolje);
                        $elektrikaPoReferencnihConah[$cona->id] = array_sum_values(
                            $elektrikaPoReferencnihConah[$cona->id],
                            $referencnaRazsvetljava->potrebnaEnergija
                        );
                        $TSSReferencniSistemiRazsvetljavaOut[] = $referencnaRazsvetljava->export();
                    }
                }
                App::saveProjectCalculation(
                    'Pures',
                    $projectId,
                    'Ref' . DS . 'TSS' . DS . 'razsvetljava',
                    $TSSReferencniSistemiRazsvetljavaOut
                );
            }
        }

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $TSSSistemiOHT = App::loadProjectData('Pures', $projectId, 'TSS' . DS . 'ogrevanje') ?? [];
        if (count($TSSSistemiOHT) > 0) {
            $TSSSistemiOHTOut = [];
            $vracljiveIzgubeVOgrevanje = [];
            foreach ($TSSSistemiOHT as $sistem) {
                $cona = array_first_callback($cone, fn($cona) => $cona->id == $sistem->idCone);
                if (!$cona) {
                    throw new \Exception('TSS OHT: Cona ne obstaja.');
                }

                $sistemOHT = SistemOHTFactory::create($sistem->vrsta, $sistem);
                if (!$sistemOHT) {
                    throw new \Exception(sprintf('TSS OHT: Sistem "%s" ne obstaja.', $sistem->id));
                }

                $sistemOHT->vracljiveIzgubeVOgrevanje = $vracljiveIzgubeVOgrevanje;
                $sistemOHT->analiza($cona, $okolje);

                $vracljiveIzgubeVOgrevanje = $sistemOHT->vracljiveIzgubeVOgrevanje;

                $elektrikaPoConah[$sistemOHT->idCone] = array_sum_values(
                    $elektrikaPoConah[$sistemOHT->idCone],
                    $sistemOHT->potrebnaElektricnaEnergija
                );

                $elektrikaPoConah[$sistemOHT->idCone] =
                    array_sum_values(
                        $elektrikaPoConah[$sistemOHT->idCone],
                        array_subtract_values(
                            $sistemOHT->potrebnaEnergija,
                            $sistemOHT->obnovljivaEnergija
                        )
                    );

                $TSSSistemiOHTOut[] = $sistemOHT->export();
            }

            App::saveProjectCalculation('Pures', $projectId, 'TSS' . DS . 'ogrevanje', $TSSSistemiOHTOut);

            // za referenčno stavbo
            if ($splosniPodatki->stavba->vrsta == 'zahtevna') {
                $TSSReferencniSistemiOHTOut = [];
                foreach ($referencneCone as $cona) {
                    $vracljiveIzgubeVOgrevanje = [];

                    $conaClass = new Cona(null, $cona);
                    $refTSSSistemiOHT = $conaClass->referencniTSS('OHT');

                    foreach ($refTSSSistemiOHT as $refSistemOHT) {
                        $referencniSistemOHT = SistemOHTFactory::create($refSistemOHT->vrsta, $refSistemOHT, true);

                        $refSistemOHT->vracljiveIzgubeVOgrevanje = $vracljiveIzgubeVOgrevanje;
                        $referencniSistemOHT->analiza($cona, $okolje);
                        $vracljiveIzgubeVOgrevanje = $referencniSistemOHT->vracljiveIzgubeVOgrevanje;

                        $elektrikaPoReferencnihConah[$refSistemOHT->idCone] = array_sum_values(
                            $elektrikaPoReferencnihConah[$refSistemOHT->idCone],
                            $referencniSistemOHT->potrebnaElektricnaEnergija
                        );
                        $elektrikaPoReferencnihConah[$refSistemOHT->idCone] =
                            array_sum_values(
                                $elektrikaPoReferencnihConah[$refSistemOHT->idCone],
                                array_subtract_values(
                                    $referencniSistemOHT->potrebnaEnergija,
                                    $referencniSistemOHT->obnovljivaEnergija
                                )
                            );

                        $TSSReferencniSistemiOHTOut[] = $referencniSistemOHT->export();
                    }
                }
                App::saveProjectCalculation(
                    'Pures',
                    $projectId,
                    'Ref' . DS . 'TSS' . DS . 'ogrevanje',
                    $TSSReferencniSistemiOHTOut
                );
            }
        }

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $TSSFotonapetostniSistemi = App::loadProjectData('Pures', $projectId, 'TSS' . DS . 'fotovoltaika') ?? [];
        if (count($TSSFotonapetostniSistemi) > 0) {
            if (!$this->validateSchema(json: $TSSFotonapetostniSistemi, schema: 'fotovoltaika', area: 'Pures')) {
                return;
            }

            $TSSFotonapetostniSistemiOut = [];
            $celotnaElektrikaVsehCon = [];
            foreach ($elektrikaPoConah as $conaId => $conaElektrika) {
                $celotnaElektrikaVsehCon = array_sum_values($celotnaElektrikaVsehCon, $conaElektrika);
            }
            foreach ($TSSFotonapetostniSistemi as $sistem) {
                $fotonapetostniSistem = new FotonapetostniSistem($sistem);
                $fotonapetostniSistem->analiza($celotnaElektrikaVsehCon, $okolje);

                $sistem->energijaPoEnergentih = $fotonapetostniSistem->energijaPoEnergentih;

                // odštej v stavbi porabljeno energijo tega fotonapetostnega sistema, da se zmanjša
                // potrebna električna energija za naslednji fotonapetostni sistem (če jih je več)
                array_subtract_values($celotnaElektrikaVsehCon, $fotonapetostniSistem->porabljenaEnergija);
                array_walk(
                    $celotnaElektrikaVsehCon,
                    function ($potrebnaEnergija, $mesec) use ($celotnaElektrikaVsehCon) {
                        if ($potrebnaEnergija < 0) {
                            $celotnaElektrikaVsehCon[$mesec] = 0;
                        }
                    }
                );

                $TSSFotonapetostniSistemiOut[] = $fotonapetostniSistem;
            }
            App::saveProjectCalculation('Pures', $projectId, 'TSS' . DS . 'fotovoltaika', $TSSFotonapetostniSistemiOut);
        }

        // za referenčno stavbo
        if ($splosniPodatki->stavba->vrsta == 'zahtevna') {
            $TSSReferencniFotonapetostniSistemiOHTOut = [];
            $celotnaElektrikaVsehReferencnihCon = [];
            foreach ($elektrikaPoReferencnihConah as $refConaId => $refConaElektrika) {
                $celotnaElektrikaVsehReferencnihCon = array_sum_values($celotnaElektrikaVsehReferencnihCon, $refConaElektrika);
            }

            $conaClass = new Cona(null, $referencneCone[0]);
            $refTSSFotonapetostniSistemi = $conaClass->referencniTSS('fotovoltaika');

            foreach ($refTSSFotonapetostniSistemi as $refSistem) {
                $refFotonapetostniSistem = new FotonapetostniSistem($refSistem);
                $refFotonapetostniSistem->analiza($celotnaElektrikaVsehReferencnihCon, $okolje);

                $refSistem->energijaPoEnergentih = $refFotonapetostniSistem->energijaPoEnergentih;

                // odštej v stavbi porabljeno energijo tega fotonapetostnega sistema, da se zmanjša
                // potrebna električna energija za naslednji fotonapetostni sistem (če jih je več)
                array_subtract_values(
                    $celotnaElektrikaVsehReferencnihCon,
                    $refFotonapetostniSistem->porabljenaEnergija
                );
                array_walk(
                    $celotnaElektrikaVsehReferencnihCon,
                    function ($potrebnaEnergija, $mesec) use ($celotnaElektrikaVsehReferencnihCon) {
                        if ($potrebnaEnergija < 0) {
                            $celotnaElektrikaVsehReferencnihCon[$mesec] = 0;
                        }
                    }
                );

                $TSSReferencniFotonapetostniSistemiOHTOut[] = $refFotonapetostniSistem;
            }

            App::saveProjectCalculation(
                'Pures',
                $projectId,
                'Ref' . DS . 'TSS' . DS . 'fotovoltaika',
                $TSSReferencniFotonapetostniSistemiOHTOut
            );
        }
    }
}
