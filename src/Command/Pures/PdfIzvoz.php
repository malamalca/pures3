<?php
declare(strict_types=1);

namespace App\Command\Pures;

use App\Core\App;
use App\Core\Command;
use App\Core\Configure;
use App\Core\PDF\PdfFactory;
use App\Core\PdfView;

class PdfIzvoz extends Command
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

        $pdfEngine = Configure::read('PDF.engine');
        $pdfLayout = Configure::read('PDF.' . $pdfEngine . '.layout');

        $view = new PdfView([], ['layout' => $pdfLayout]);
        $view->set('projectId', $projectId);
        $view->set('splosniPodatki', App::loadProjectData('Pures', $projectId, 'splosniPodatki'));
        $view->set('okolje', App::loadProjectCalculation('Pures', $projectId, 'okolje'));

        $stavba = App::loadProjectCalculation('Pures', $projectId, 'stavba');
        $view->set('stavba', $stavba);
        if ($stavba->vrsta == 'zahtevna') {
            $view->set('refStavba', App::loadProjectCalculation('Pures', $projectId, 'stavba_ref'));
        }

        $view->set('cone', App::loadProjectCalculation('Pures', $projectId, 'cone'));
        $view->set(
            'tKons',
            App::loadProjectCalculation('Pures', $projectId, 'konstrukcije' . DS . 'transparentne') ?? []
        );
        $view->set(
            'ntKons',
            App::loadProjectCalculation('Pures', $projectId, 'konstrukcije' . DS . 'netransparentne') ?? []
        );
        $view->set(
            'sistemiOgrevanja',
            (array)App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'ogrevanje')
        );
        $view->set(
            'sistemiRazsvetljave',
            (array)App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'razsvetljava')
        );
        $view->set(
            'sistemiPrezracevanja',
            (array)App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'prezracevanje')
        );
        $view->set('sistemiSTPE', (array)App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'fotovoltaika'));

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

        $view->set('energentiSistema', $energentiSistema);
        $view->set('vgrajeniSistemi', $vgrajeniSistemi);

        $this->izkaz($projectId, $view);
        $this->elaborat($projectId, $view);
    }

    /**
     * Izvoz elaborata v pdf
     *
     * @param string $projectId Project id.
     * @param \App\Core\View $view View object
     * @return void
     */
    private function elaborat($projectId, $view)
    {
        $pdfEngine = Configure::read('PDF.engine');
        $pdf = PdfFactory::create($pdfEngine, Configure::read('PDF.' . $pdfEngine, []));

        $pdf->newPage((string)$view->render('Projekti', 'naslovnica'));

        $sourceFilename = App::getProjectFolder('Pures', $projectId, 'podatki') . 'tehnicnoPorocilo.md';
        if (file_exists($sourceFilename)) {
            //$porocilo = file_get_contents($sourceFilename);
            ob_start();
            require_once $sourceFilename;
            $porocilo = ob_get_contents();
            ob_end_clean();
            if (!empty($porocilo)) {
                $porociloPages = explode('<!-- NEW PAGE -->', $porocilo);
                foreach ($porociloPages as $porociloPage) {
                    $view->set('porocilo', $porociloPage);
                    $porocilo = (string)$view->render('Projekti', 'porocilo');

                    $pdf->newPage($porocilo);
                }
            }
        }

        $pdf->newPage((string)$view->render('Projekti', 'view'));
        $pdf->newPage((string)$view->render('Projekti', 'analiza'));

        foreach ($view->get('ntKons') as $kons) {
            $view->set('kons', $kons);
            $pdf->newPage((string)$view->render('Konstrukcije', 'view'));
        }

        foreach ($view->get('cone') as $cona) {
            $view->set('cona', $cona);
            $pdf->newPage((string)$view->render('Cone', 'ovoj'));
            $pdf->newPage((string)$view->render('Cone', 'analiza'));
        }

        foreach ($view->get('sistemiOgrevanja') as $sistem) {
            $view->set('sistem', $sistem);
            $pdf->newPage((string)$view->render('TSS', 'ogrevanje'));
        }

        foreach ($view->get('sistemiPrezracevanja') as $sistem) {
            $view->set('sistem', $sistem);
            $pdf->newPage((string)$view->render('TSS', 'prezracevanje'));
        }

        foreach ($view->get('sistemiRazsvetljave') as $sistem) {
            $view->set('sistem', $sistem);
            $pdf->newPage((string)$view->render('TSS', 'razsvetljava'));
        }

        foreach ($view->get('sistemiSTPE') as $sistem) {
            $view->set('sistem', $sistem);
            $pdf->newPage((string)$view->render('TSS', 'fotovoltaika'));
        }

        $pdfFolder = App::getProjectFolder('Pures', $projectId, 'pdf');
        if (!is_dir($pdfFolder)) {
            mkdir($pdfFolder, 0777, true);
        }

        $pdf->newPage((string)$view->render('Projekti', 'snes'));

        $pdf->saveAs($pdfFolder . 'PuresElaborat.pdf');
    }

    /**
     * Izvoz izkaza v pdf
     *
     * @param string $projectId Project id.
     * @param \App\Core\View $view View object.
     * @return void
     */
    private function izkaz($projectId, $view)
    {
        $splosniPodatki = $view->render('Izkazi', 'splosniPodatki');
        $podrocjeGf = $view->render('Izkazi', 'podrocjeGf');
        $podrocjeSnes = $view->render('Izkazi', 'podrocjeSNES');

        $pdfEngine = Configure::read('PDF.engine');
        $pdf = PdfFactory::create($pdfEngine, Configure::read('PDF.' . $pdfEngine, []));

        $pdf->newPage((string)$splosniPodatki);
        $pdf->newPage((string)$podrocjeGf);
        $pdf->newPage((string)$podrocjeSnes);

        $pdfFolder = App::getProjectFolder('Pures', $projectId, 'pdf');
        if (!is_dir($pdfFolder)) {
            mkdir($pdfFolder, 0777, true);
        }

        $pdf->saveAs($pdfFolder . 'PuresIzkaz.pdf');
    }
}
