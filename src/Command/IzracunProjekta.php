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
     * @param array|null $args Additional arguments
     * @return void
     */
    public function run($projectId = null, ...$args)
    {
        parent::run();

        (new IzracunOkolja())->run($projectId);
        (new IzracunKonstrukcij())->run($projectId);
        (new IzracunCone())->run($projectId);
        (new IzracunTSS())->run($projectId);
        (new IzracunStavbe())->run($projectId);
        (new PdfIzvoz())->run($projectId);
    }
}
