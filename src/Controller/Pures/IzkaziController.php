<?php
declare(strict_types=1);

namespace App\Controller\Pures;

use App\Core\App;

class IzkaziController
{
    /**
     * Prvi del izkaza - splošni podatki
     *
     * @param string $projectId Building name
     * @return void
     */
    public function splosniPodatki($projectId)
    {
        App::set('splosniPodatki', App::loadProjectData('Pures', $projectId, 'splosniPodatki'));
        App::set('stavba', App::loadProjectCalculation('Pures', $projectId, 'stavba'));
        App::set('cone', App::loadProjectCalculation('Pures', $projectId, 'cone'));
        App::set('okolje', App::loadProjectCalculation('Pures', $projectId, 'okolje'));

        $vgrajeniSistemi = [];

        $tssOgrevanje = App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'ogrevanje');
        $tssRazsvetljava = App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'razsvetljava');
        $tssPrezracevanje = App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'prezracevanje');
        $tssFotovoltaika = App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'fotovoltaika');

        $energentiSistema = [];
        if ($tssOgrevanje) {
            foreach ($tssOgrevanje as $sistem) {
                /** @var \App\Calc\GF\TSS\OgrevalniSistemi\OHTSistem $sistem */
                if (isset($sistem->ogrevanje)) {
                    $vgrajeniSistemi[] = 'ogrevanje';
                    foreach ($sistem->energijaPoEnergentih as $energent => $energija) {
                        $energentiSistema['ogrevanje'][] = $energent;
                    }
                }
                if (isset($sistem->tsv)) {
                    $vgrajeniSistemi[] = 'tsv';
                    foreach ($sistem->energijaPoEnergentih as $energent => $energija) {
                        $energentiSistema['tsv'][] = $energent;
                    }
                }
                if (isset($sistem->hlajenje)) {
                    $vgrajeniSistemi[] = 'hlajenje';
                    foreach ($sistem->energijaPoEnergentih as $energent => $energija) {
                        $energentiSistema['hlajenje'][] = $energent;
                    }
                }
                if (!isset($sistem->ogrevanje) && !isset($sistem->tsv) && !isset($sistem->hlajenje)) {
                    $vgrajeniSistemi[] = 'ogrevanje';
                    foreach ($sistem->energijaPoEnergentih as $energent => $energija) {
                        $energentiSistema['ogrevanje'][] = $energent;
                    }
                }
            }
            if (isset($energentiSistema['ogrevanje'])) {
                $energentiSistema['ogrevanje'] = array_unique($energentiSistema['ogrevanje']);
            }
            if (isset($energentiSistema['tsv'])) {
                $energentiSistema['tsv'] = array_unique($energentiSistema['tsv']);
            }
            if (isset($energentiSistema['hlajenje'])) {
                $energentiSistema['hlajenje'] = array_unique($energentiSistema['hlajenje']);
            }
        }

        if ($tssRazsvetljava) {
            $vgrajeniSistemi[] = 'razsvetljava';
            $energentiSistema['razsvetljava'] = [];
            foreach ($tssRazsvetljava as $sistem) {
                foreach ($sistem->energijaPoEnergentih as $energent => $energija) {
                    $energentiSistema['razsvetljava'][] = $energent;
                }
            }
            $energentiSistema['razsvetljava'] = array_unique($energentiSistema['razsvetljava']);
        }

        if ($tssPrezracevanje) {
            $vgrajeniSistemi[] = 'prezracevanje';
            $energentiSistema['prezracevanje'] = [];
            foreach ($tssPrezracevanje as $sistem) {
                foreach ($sistem->energijaPoEnergentih as $energent => $energija) {
                    $energentiSistema['prezracevanje'][] = $energent;
                }
            }
            $energentiSistema['prezracevanje'] = array_unique($energentiSistema['prezracevanje']);
        }

        if ($tssFotovoltaika) {
            $vgrajeniSistemi[] = 'fotovoltaika';
            $energentiSistema['fotovoltaika'] = [];
            foreach ($tssFotovoltaika as $sistem) {
                foreach ($sistem->energijaPoEnergentih as $energent => $energija) {
                    $energentiSistema['fotovoltaika'][] = $energent;
                }
            }
            $energentiSistema['fotovoltaika'] = array_unique($energentiSistema['fotovoltaika']);
        }

        App::set('energentiSistema', $energentiSistema);
        App::set('vgrajeniSistemi', $vgrajeniSistemi);
    }

    /**
     * Prvi del izkaza - splošni podatki
     *
     * @param string $projectId Building name
     * @return void
     */
    public function podrocjeGf($projectId)
    {
        $stavba = App::loadProjectCalculation('Pures', $projectId, 'stavba');
        App::set('stavba', $stavba);
        if ($stavba->vrsta == 'zahtevna') {
            App::set('refStavba', App::loadProjectCalculation('Pures', $projectId, 'stavba_ref'));
        }

        App::set(
            'tKons',
            App::loadProjectCalculation('Pures', $projectId, 'konstrukcije' . DS . 'transparentne') ?? []
        );
        App::set(
            'ntKons',
            App::loadProjectCalculation('Pures', $projectId, 'konstrukcije' . DS . 'netransparentne') ?? []
        );
        App::set('cone', App::loadProjectCalculation('Pures', $projectId, 'cone'));
        App::set('okolje', App::loadProjectCalculation('Pures', $projectId, 'okolje'));
    }

    /**
     * Drugi del izkaza - sNes
     *
     * @param string $projectId Building name
     * @return void
     */
    public function podrocjeSNES($projectId)
    {
        App::set('stavba', App::loadProjectCalculation('Pures', $projectId, 'stavba'));
        App::set('cone', App::loadProjectCalculation('Pures', $projectId, 'cone'));
        App::set('sistemiOgrevanja', App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'ogrevanje'));
        App::set('sistemiRazsvetljave', App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'razsvetljava'));
        App::set(
            'sistemiPrezracevanja',
            App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'prezracevanje')
        );
    }
}
