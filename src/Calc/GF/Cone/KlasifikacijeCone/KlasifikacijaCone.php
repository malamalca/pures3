<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;

abstract class KlasifikacijaCone
{
    public float $notranjaTOgrevanje;
    public float $notranjaTHlajenje;

    /**
     * Izračun energije za TSV za specifični mesec
     *
     * @param int $mesec Številka meseca
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return float
     */
    abstract public function izracunTSVZaMesec(int $mesec, Cona $cona): float;

    /**
     * Export za json
     *
     * @return mixed
     */
    abstract public function export();
}
