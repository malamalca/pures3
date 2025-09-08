<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;

class IndustrijskaKlasifikacijaCone extends KlasifikacijaCone
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
            $energijaTSV = 1.5 * $cona->TSV->steviloOseb * $stDni;
        } else {
            $energijaTSV = 75 * $cona->ogrevanaPovrsina / 1000 * $stDni;
        }

        return $energijaTSV;
    }

    /**
     * @inheritDoc
     */
    public function kolicinaSvezegaZrakaZaPrezracevanje(Cona $cona): float
    {
        $stOseb = 0.05 * $cona->ogrevanaPovrsina;
        $kolicinaZrakaNaOsebo = 25.2; // [m3/h] [7 l/s * 3600s / 1000 l/m3]
        $faktorSocasneUporabe = 0.6;
        $dnevnaUporabaStavbe = 12; // [h]
        $tedenskaUporabaStavbe = 5; // [dni/teden]

        $volumenZraka = $kolicinaZrakaNaOsebo * $faktorSocasneUporabe * $stOseb *
            $dnevnaUporabaStavbe / 24 * $tedenskaUporabaStavbe / 7;

        return $volumenZraka;
    }

    /**
     * @inheritDoc
     */
    public function letnoSteviloUrDelovanjaRazsvetljave(Cona $cona): array
    {
        return ['podnevi' => 1800, 'ponoci' => 200];
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
