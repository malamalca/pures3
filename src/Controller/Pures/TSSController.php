<?php
declare(strict_types=1);

namespace App\Controller\Pures;

use App\Core\App;
use App\Core\Controller;

class TSSController extends Controller
{
    /**
     * Prikaz podatkov o ovoju cone
     *
     * @param string $projectId Building name
     * @param string $sistemId Id
     * @param string|null $ref Referenčna stavba - parameter mora biti 'ref', da se pokažejo podatki referenčne stavbe
     * @return void
     */
    public function prezracevanje($projectId, $sistemId, $ref = null)
    {
        App::set('projectId', $projectId);

        $sistemi = App::loadProjectCalculation(
            'Pures',
            $projectId,
            ($ref == 'ref' ? 'Ref' . DS : '') . 'TSS' . DS . 'prezracevanje'
        );
        App::set('sistemi', $sistemi);
        App::set('sistem', array_first_callback($sistemi, fn($sistem) => strtolower($sistem->id) == strtolower($sistemId)));
    }

    /**
     * Prikaz podatkov o razsvetljavi cone
     *
     * @param string $projectId Building name
     * @param string $sistemId Id
     * @param string|null $ref Referenčna stavba - parameter mora biti 'ref', da se pokažejo podatki referenčne stavbe
     * @return void
     */
    public function razsvetljava($projectId, $sistemId, $ref = null)
    {
        App::set('projectId', $projectId);

        $sistemi = App::loadProjectCalculation(
            'Pures',
            $projectId,
            ($ref == 'ref' ? 'Ref' . DS : '') . 'TSS' . DS . 'razsvetljava'
        );
        App::set('sistemi', $sistemi);
        App::set('sistem', array_first_callback($sistemi, fn($sistem) => strtolower($sistem->id) == strtolower($sistemId)));
    }

    /**
     * Prikaz podatkov o sistemu ogrevanja
     *
     * @param string $projectId Building name
     * @param string $sistemId Id
     * @param string|null $ref Referenčna stavba - parameter mora biti 'ref', da se pokažejo podatki referenčne stavbe
     * @return void
     */
    public function oht($projectId, $sistemId, $ref = null)
    {
        App::set('projectId', $projectId);

        $jeReferencnaStavba = (!empty($ref) && strtolower($ref) == 'ref');

        $sistemi = App::loadProjectCalculation(
            'Pures',
            $projectId,
            ($jeReferencnaStavba ? 'Ref' . DS : '') . 'TSS' . DS . 'ogrevanje'
        );

        $sistem = array_first_callback($sistemi, fn($sistem) => strtolower($sistem->id) == strtolower($sistemId));

        if (!$sistem) {
            throw new \Exception(sprintf('Sistem z id:"%s" ne obstaja.', $sistemId));
        }

        App::set('sistemi', $sistemi);
        App::set('sistem', $sistem);
        App::set('jeReferencnaStavba', $jeReferencnaStavba);
    }

    /**
     * Prikaz podatkov o sistemu fotovoltaike
     *
     * @param string $projectId Building name
     * @param string $sistemId Id
     * @return void
     */
    public function fotovoltaika($projectId, $sistemId)
    {
        App::set('projectId', $projectId);

        $sistemi = App::loadProjectCalculation('Pures', $projectId, 'TSS' . DS . 'fotovoltaika');
        App::set('sistemi', $sistemi);
        App::set('sistem', array_first_callback($sistemi, fn($sistem) => strtolower($sistem->id) == strtolower($sistemId)));
    }
}
