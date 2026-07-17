<?php
declare(strict_types=1);

namespace App\Controller\Pures;

use App\Calc\GF\Cone\Cona;
use App\Core\App;
use App\Core\Controller;

class ConeController extends Controller
{
    /**
     * Naloži cone in vanje vstavi konstrukcije (iz konstrukcije/*.json), poiskane po idKonstrukcije.
     * cone.json ne shranjuje več celotne konstrukcije, zato jo je za prikaz treba ponovno vstaviti.
     *
     * @param string $projectId Building name
     * @param string|null $ref Referenčna stavba ('ref')
     * @return array
     */
    private function naloziCone($projectId, $ref = null): array
    {
        $prefix = ($ref == 'ref' ? 'Ref' . DS : '');
        $cone = App::loadProjectCalculation('Pures', $projectId, $prefix . 'cone') ?? [];

        $konstrukcije = new \stdClass();
        $konstrukcije->netransparentne =
            App::loadProjectCalculation('Pures', $projectId, $prefix . 'konstrukcije' . DS . 'netransparentne') ?? [];
        $konstrukcije->transparentne =
            App::loadProjectCalculation('Pures', $projectId, $prefix . 'konstrukcije' . DS . 'transparentne') ?? [];

        return Cona::hydrateKonstrukcije($cone, $konstrukcije);
    }

    /**
     * Prikaz podatkov o ovoju cone
     *
     * @param string $projectId Building name
     * @param string $conaId Id cone
     * @param string|null $ref Referenčna stavba
     * @return void
     */
    public function ovoj($projectId, $conaId, $ref = null)
    {
        $cone = $this->naloziCone($projectId, $ref);

        App::set('projectId', $projectId);
        App::set('cona', array_first_callback($cone, fn($cona) => strtolower($cona->id) == strtolower($conaId)));
    }

    /**
     * Prikaz podatkov o transmisiji
     *
     * @param string $projectId Building name
     * @param string $conaId Id cone
     * @param string|null $ref Referenčna stavba
     * @return void
     */
    public function transmisija($projectId, $conaId, $ref = null)
    {
        $cone = $this->naloziCone($projectId, $ref);

        App::set('projectId', $projectId);
        App::set('cona', array_first_callback($cone, fn($cona) => strtolower($cona->id) == strtolower($conaId)));
        App::set('okolje', App::loadProjectCalculation('Pures', $projectId, 'okolje'));
    }

    /**
     * Prikaz analize cone
     *
     * @param string $projectId Building name
     * @param string $conaId Id cone
     * @param string|null $ref Referenčna stavba
     * @return void
     */
    public function analiza($projectId, $conaId, $ref = null)
    {
        $cone = $this->naloziCone($projectId, $ref);

        App::set('projectId', $projectId);
        App::set('cona', array_first_callback($cone, fn($cona) => strtolower($cona->id) == strtolower($conaId)));
    }

    /**
     * Prikaz transparentne konstrukcije cone
     *
     * @param string $projectId Building name
     * @param string $conaId Id cone
     * @param string $konsId Id konstrukcije
     * @return void
     */
    public function transparentniElement($projectId, $conaId, $konsId)
    {
        $cone = $this->naloziCone($projectId);
        $cona = array_first_callback($cone, fn($cona) => strtolower($cona->id) == strtolower($conaId));
        $k = array_first_callback(
            $cona->ovoj->transparentneKonstrukcije,
            fn($kn) => strtolower($kn->id) == strtolower($konsId)
        );

        App::set('projectId', $projectId);
        App::set('cona', $cona);
        App::set('kons', $k);
    }
}
