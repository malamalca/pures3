<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;

class VecstanovanjskaKlasifikacijaCone extends EnostanovanjskaKlasifikacijaCone
{
    public string $code = 'St-2';

    public float $notranjaTOgrevanje = 20;
    public float $notranjaTHlajenje = 26;

    public int $toplaVodaT = 42;
    public int $hladnaVodaT = 10;

    /**
     * @inheritDoc
     */
    public function izracunTSVZaMesec(int $mesec, Cona $cona): float
    {
        $toplaVodaT = $this->TSV->toplaVodaT ?? $this->toplaVodaT;
        $hladnaVodaT = $this->TSV->hladnaVodaT ?? $this->hladnaVodaT;

        if (empty($cona->TSV->steviloOseb)) {
            // EN 12831-3:2017 B.3
            if ($cona->ogrevanaPovrsina > 50) {
                $steviloOsebEq = 0.035 * $cona->ogrevanaPovrsina;
            } elseif ($cona->ogrevanaPovrsina < 10) {
                $steviloOsebEq = 1.0;
            } else {
                $steviloOsebEq = 1.75 - 0.01875 * (50 - $cona->ogrevanaPovrsina);
            }
            if ($steviloOsebEq > 1.75) {
                $steviloOseb = 1.75 + 0.3 * ($steviloOsebEq - 1.75);
            } else {
                $steviloOseb = $steviloOsebEq;
            }

            $cona->TSV->steviloOseb = $steviloOseb;
        } else {
            $steviloOseb = $cona->TSV->steviloOseb;
        }

        if (empty($cona->TSV->dnevnaKolicina)) {
            $dnevnaKolicina = min(40.71, 3.26 * $cona->ogrevanaPovrsina / $steviloOseb);
            $cona->TSV->dnevnaKolicina = $dnevnaKolicina;
        } else {
            $dnevnaKolicina = $cona->TSV->dnevnaKolicina;
        }

        $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

        $energijaTSV = 0.001 * $dnevnaKolicina * $steviloOseb * 4.2 / 3.6 *
            ($toplaVodaT - $hladnaVodaT) * $stDni -
            ($cona->vrnjeneIzgubeVTSV[$mesec] ?? 0);

        return $energijaTSV;
    }
}
