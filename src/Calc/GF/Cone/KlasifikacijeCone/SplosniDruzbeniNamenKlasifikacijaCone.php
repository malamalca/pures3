<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;

class SplosniDruzbeniNamenKlasifikacijaCone extends KlasifikacijaCone
{
    public string $code = 'In-1';

    public float $notranjaTOgrevanje = 22;
    public float $notranjaTHlajenje = 25;

    /**
     * @inheritDoc
     */
    public function izracunTSVZaMesec(int $mesec, Cona $cona): float
    {
        $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

        if (isset($cona->TSV->steviloOseb)) {
            $energijaTSV = 3.5 * $cona->TSV->steviloOseb * $stDni;
        } else {
            $energijaTSV = 230 * $cona->ogrevanaPovrsina / 1000 * $stDni;
        }

        return $energijaTSV;
    }

    /**
     * @inheritDoc
     */
    public function kolicinaSvezegaZrakaZaPrezracevanje(Cona $cona): float
    {
        $stOseb = 0.06 * $cona->ogrevanaPovrsina;
        $kolicinaZrakaNaOsebo = 57.6; // [m3/h] [16 l/s * 3600s / 1000 l/m3]
        $faktorSocasneUporabe = 0.6;
        $dnevnaUporabaStavbe = 16; // [h]
        $tedenskaUporabaStavbe = 7; // [dni/teden]

        $volumenZraka = $kolicinaZrakaNaOsebo * $faktorSocasneUporabe * $stOseb *
            $dnevnaUporabaStavbe / 24 * $tedenskaUporabaStavbe / 7;

        return $volumenZraka;
    }

    /**
     * @inheritDoc
     */
    public function letnoSteviloUrDelovanjaRazsvetljave(Cona $cona): array
    {
        return ['podnevi' => 3000, 'ponoci' => 2000];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSRazsvetljava(Cona $cona): array
    {
        // TODO
        return [];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSPrezracevanja(Cona $cona): array
    {
        // TODO
        return [];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSOHT(Cona $cona): array
    {
        // TODO
        return [];
    }

    /**
     * @inheritDoc
     */
    public function export()
    {
        return $this->code;
    }
}
