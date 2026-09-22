<?php
declare(strict_types=1);

namespace App\Command\Hrup;

use App\Calc\Hrup\OdmevniHrup\Prostor;
use App\Core\App;
use App\Core\Command;

class IzracunOdmevnegaHrupa extends Command
{
    /**
     * Izračuna prostore iz odmevniHrup.json oziroma istoimenske mape.
     *
     * @param string|null $projectId Id projekta
     * @return void
     */
    public function run($projectId = null)
    {
        parent::run();

        $prostori = App::loadProjectData('Hrup', $projectId, 'odmevniHrup');
        if ($prostori === null) {
            return;
        }
        if (!is_array($prostori) || !$this->validateSchema($prostori, 'odmevniHrup', 'Hrup')) {
            throw new \InvalidArgumentException('Neveljavni vhodni podatki za odmevni hrup.');
        }

        $rezultati = [];
        $ids = [];
        foreach ($prostori as $config) {
            if (in_array($config->id, $ids, true)) {
                throw new \InvalidArgumentException('Podvojen id prostora: ' . $config->id);
            }
            $ids[] = $config->id;
            $prostor = new Prostor($config);
            $prostor->analiza();
            $rezultati[] = $prostor->export();
        }
        App::saveProjectCalculation('Hrup', $projectId, 'odmevniHrup', $rezultati);
    }
}
