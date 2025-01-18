<?php
declare(strict_types=1);

namespace App\Command\Pures;

use App\Core\App;
use App\Core\Command;
use App\Core\Xml;

class XmlIzvoz extends Command
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

        $export = [];
        $export['splosniPodatki'] = App::loadProjectData('Pures', $projectId, 'splosniPodatki');
        $export['cone'] = App::loadProjectData('Pures', $projectId, 'cone');

        $sourceFilename = App::getProjectFolder('Pures', $projectId, 'podatki') . 'tehnicnoPorocilo.md';
        if (file_exists($sourceFilename)) {
            ob_start();
            require_once $sourceFilename;
            $porocilo = ob_get_contents();
            ob_end_clean();
            if (!empty($porocilo)) {
                $export['tehnicnoPorocilo'] = $porocilo;
            }
        }

        $export['tss']['ogrevanje'] = App::loadProjectData('Pures', $projectId, 'TSS' . DS . 'ogrevanje');
        $export['tss']['hlajenje'] = App::loadProjectData('Pures', $projectId, 'TSS' . DS . 'hlajenje');
        $export['tss']['razsvetljava'] = App::loadProjectData('Pures', $projectId, 'TSS' . DS . 'razsvetljava');
        $export['tss']['prezracevanje'] = App::loadProjectData('Pures', $projectId, 'TSS' . DS . 'prezracevanje');
        $export['tss']['fotovoltaika'] = App::loadProjectData('Pures', $projectId, 'TSS' . DS . 'fotovoltaika');

        /** @var \DOMDocument $xml */
        $xml = Xml::fromArray(['PHPures' => $export], ['format' => 'tags', 'return' => 'domdocument']);
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;
        $xml = $xml->saveXML();

        $xmlFolder = App::getProjectFolder('Pures', $projectId, 'xml');
        if (!is_dir($xmlFolder)) {
            mkdir($xmlFolder, 0777, true);
        }

        $result = file_put_contents($xmlFolder . 'export.xml', $xml);
    }
}
