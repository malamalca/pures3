<?php
declare(strict_types=1);

namespace App\Command\Hrup;

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

        (new IzracunElementov())->run($projectId);
        (new IzracunZunanjegaHrupa())->run($projectId);
        (new IzracunZracnegaHrupa())->run($projectId);
        (new IzracunUdarnegaHrupa())->run($projectId);
        (new PdfIzvoz())->run($projectId);
    }
}
