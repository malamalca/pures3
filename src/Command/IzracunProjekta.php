<?php
declare(strict_types=1);

namespace App\Command;

use App\Core\Command;

class IzracunProjekta extends Command
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

        (new IzracunOkolja())->run($projectId);
        (new IzracunKonstrukcij())->run($projectId);
        (new IzracunCone())->run($projectId);
        (new IzracunTSS())->run($projectId);
        (new IzracunStavbe())->run($projectId);
        (new PdfIzkaz())->run($projectId);
    }
}
