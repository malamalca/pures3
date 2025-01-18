<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;

class PoslovnaKlasifikacijaCone extends KlasifikacijaCone
{
    protected string $code = 'Po-1';

    /**
     * @inheritDoc
     */
    public function izracunTSVZaMesec(int $mesec, Cona $cona): float
    {
        $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

        if (isset($cona->TSV->steviloOseb)) {
            $energijaTSV = 0.4 * $cona->TSV->steviloOseb * $stDni;
        } else {
            $energijaTSV = 30 * $cona->ogrevanaPovrsina / 1000 * $stDni;
        }

        return $energijaTSV;
    }

    /**
     * @inheritDoc
     */
    public function export()
    {
        return $this->code;
    }
}
