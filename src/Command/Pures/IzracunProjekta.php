<?php
declare(strict_types=1);

namespace App\Command\Pures;

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

        (new IzracunOkolja())->run($projectId, ...$args);
        (new IzracunKonstrukcij())->run($projectId, ...$args);
        (new IzracunCone())->run($projectId, ...$args);
        (new IzracunTSS())->run($projectId, ...$args);
        (new IzracunStavbe())->run($projectId, ...$args);
        if (!in_array('--noPdf', $args)) {
            (new PdfIzvoz())->run($projectId, ...$args);
        }
    }
}
