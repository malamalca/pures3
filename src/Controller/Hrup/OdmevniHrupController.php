<?php
declare(strict_types=1);

namespace App\Controller\Hrup;

use App\Core\App;
use App\Core\Controller;

class OdmevniHrupController extends Controller
{
    /**
     * Prikaz prostora pred namestitvijo absorberjev in po njej.
     *
     * @param string $projectId Id projekta
     * @param string $prostorId Id prostora
     * @return void
     */
    public function view($projectId, $prostorId)
    {
        $prostori = App::loadProjectCalculation('Hrup', $projectId, 'odmevniHrup') ?? [];
        $prostor = array_first_callback($prostori, fn($p) => $p->id === $prostorId);
        if (!$prostor) {
            throw new \InvalidArgumentException('Prostor za odmevni hrup ne obstaja: ' . $prostorId);
        }
        App::set('projectId', $projectId);
        App::set('prostor', $prostor);
    }
}
