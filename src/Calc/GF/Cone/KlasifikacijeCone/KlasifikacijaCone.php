<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;

abstract class KlasifikacijaCone
{
    public string $code;

    // Tabela 6.1.2:
    public float $notranjaTOgrevanje;
    public float $notranjaTHlajenje;

    public int $toplaVodaT = 42;
    public int $hladnaVodaT = 10;
    public ?\stdClass $options;

    /**
     * Class Constructor
     *
     * @param string $code Zone Code
     * @param \stdClass|null $options Dodatne nastavitve
     * @return void
     */
    public function __construct(string $code, ?\stdClass $options)
    {
        $this->code = $code;
        $this->options = $options;
    }

    /**
     * Izračun energije za TSV za specifični mesec
     * TSG Tabela 8.11.1 - Potrebna toplota za TSV v nestanovanjskih energetsko manj zahtevnih stavbah
     *
     * @param int $mesec Številka meseca
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return float
     */
    abstract public function izracunTSVZaMesec(int $mesec, Cona $cona): float;

    /**
     * Izračun količine oz. izmenjave svežega zraka za prezračevanje
     * Tabela 6.1.4: Spremenljivke, ki se upoštevajo pri določitvi količine svežega zraka za prezračevanje
     * energetsko manj zahtevnih (nestanovanjskih) stavb
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return float
     */
    abstract public function kolicinaSvezegaZrakaZaPrezracevanje(Cona $cona): float;

    /**
     * Izračun letnega števila delovanja električne razsvetljave
     * Tabela 8.17: Letno število ur delovanja električne razsvetljave posamezno cono (zn) v
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return array<int> [Podnevi, Ponoči]
     */
    abstract public function letnoSteviloUrDelovanjaRazsvetljave(Cona $cona): array;

    /**
     * Vrne lastnosti TSS za razsvetljavo referenčne stavbe
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return array
     */
    abstract public function referencniTSSRazsvetljava(Cona $cona): array;

    /**
     * Vrne lastnosti TSS za prezračevanje referenčne stavbe
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return array
     */
    abstract public function referencniTSSPrezracevanja(Cona $cona): array;

    /**
     * Vrne lastnosti TSS za OHT referenčne stavbe
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return array
     */
    abstract public function referencniTSSOHT(Cona $cona): array;

    /**
     * Export za json
     *
     * @return mixed
     */
    abstract public function export();
}
