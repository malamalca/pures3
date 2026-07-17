<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;
use App\Lib\Calc;

class KulturaRazvedriloKlasifikacijaCone extends KlasifikacijaCone
{
    public string $code = 'Ra-1';

    public float $notranjaTOgrevanje = 22;
    public float $notranjaTHlajenje = 25;

    /**
     * @inheritDoc
     */
    public function izracunTSVZaMesec(int $mesec, Cona $cona): float
    {
        // TSG tabela 8.11.1: stavbe za kulturno razvedrilo - 30 Wh/(m2 d) oz. 0,4 kWh na osebo.
        $stDni = Calc::steviloDni($mesec);

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
    public function kolicinaSvezegaZrakaZaPrezracevanje(Cona $cona): float
    {
        // TSG tabela 6.1.4: Ra-1 - 7 l/s na osebo, 0,06 oseb/m2, fu = 0,6, 15 h/dan, 7 dni/teden.
        $stOseb = 0.06 * $cona->ogrevanaPovrsina;
        $kolicinaZrakaNaOsebo = 25.2; // [m3/h] [7 l/s * 3600s / 1000 l/m3]
        $faktorSocasneUporabe = 0.6;
        $dnevnaUporabaStavbe = 15; // [h]
        $tedenskaUporabaStavbe = 7; // [dni/teden]

        $volumenZraka = $kolicinaZrakaNaOsebo * $faktorSocasneUporabe * $stOseb *
            $dnevnaUporabaStavbe / 24 * $tedenskaUporabaStavbe / 7;

        return $volumenZraka;
    }

    /**
     * @inheritDoc
     */
    public function letnoSteviloUrDelovanjaRazsvetljave(): array
    {
        // TSG tabela 8.17 nima posebne vrstice za stavbe za kulturo in razvedrilo. Ker tabela 11.8 te stavbe
        // obravnava skupaj z izobraževalnimi (Iz-1, Iz-2), se privzamejo iste ure (tD = 1800, tN = 200).
        return ['podnevi' => 1800, 'ponoci' => 200];
    }

    /**
     * @inheritDoc
     */
    public function referencnaOsvetlitevDelovnePovrsine(): int
    {
        // TSG tabela 11.8: projektirana osvetljenost delovne površine 500 lx.
        return 500;
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSRazsvetljava(Cona $cona): array
    {
        // TSG tabela 11.8: projektirana osvetljenost delovne površine 500 lx, FDS = 0,0 %.
        return $this->refRazsvetljava($cona, $this->referencnaOsvetlitevDelovnePovrsine());
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSPrezracevanja(Cona $cona): array
    {
        // TSG tabela 11.8: mehansko prezračevanje z vračanjem toplote (65 %), konstanten pretok, razred AB 3.
        return $this->refPrezracevanjeCentralno($cona);
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSOHT(Cona $cona): array
    {
        // TSG tabela 11.8 - Referenčni TSS v stavbah za kulturo in razvedrilo (Ra-1), skupaj z izobraževalnimi:
        // - ogrevanje: centralni toplovodni sistem, plinski kondenzacijski kotel (105 %), dvocevni razvod 55/45 °C,
        //   ploščata ogrevala s PI 1 K termostatskimi ventili z motornim pogonom;
        // - TSV: lokalni električni grelniki (10 l, 1 grelnik na 100 m2 Ause, 45/10 °C, ON/OFF);
        // - hlajenje: multisplit z direktnim uparjanjem, COPref = 3,0.
        return [
            $this->refToplovodniSistem($cona, 'radiatorji', 'elektricni'),
            $this->refHlajenjeMultiSplit($cona),
        ];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSFotovoltaika(Cona $cona): array
    {
        // TSG tabela 11.8, vrstica OVE: površina fotonapetostnih modulov 0,04 x Ause, polikristalne Si celice.
        return $this->refFotovoltaika($cona, 0.04, 'polikristalne');
    }

    /**
     * @inheritDoc
     */
    public function export()
    {
        return $this->code;
    }
}
