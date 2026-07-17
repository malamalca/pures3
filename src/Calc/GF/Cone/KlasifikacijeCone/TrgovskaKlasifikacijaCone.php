<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;
use App\Lib\Calc;

class TrgovskaKlasifikacijaCone extends KlasifikacijaCone
{
    public string $code = 'Tr-1';

    public float $notranjaTOgrevanje = 22;
    public float $notranjaTHlajenje = 25;

    /**
     * @inheritDoc
     */
    public function izracunTSVZaMesec(int $mesec, Cona $cona): float
    {
        // TSG tabela 8.11.1: trgovske stavbe - 10 Wh/(m2 d) prodajne površine oz. 1,0 kWh na zaposlenega.
        $stDni = Calc::steviloDni($mesec);

        if (isset($cona->TSV->steviloOseb)) {
            $energijaTSV = 1.0 * $cona->TSV->steviloOseb * $stDni;
        } else {
            $energijaTSV = 10 * $cona->ogrevanaPovrsina / 1000 * $stDni;
        }

        return $energijaTSV;
    }

    /**
     * @inheritDoc
     */
    public function kolicinaSvezegaZrakaZaPrezracevanje(Cona $cona): float
    {
        // TSG tabela 6.1.4: Tr-1 - 12 l/s na osebo, 0,06 oseb/m2, fu = 0,62, 15 h/dan, 7 dni/teden.
        $stOseb = 0.06 * $cona->ogrevanaPovrsina;
        $kolicinaZrakaNaOsebo = 43.2; // [m3/h] [12 l/s * 3600s / 1000 l/m3]
        $faktorSocasneUporabe = 0.62;
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
        // TSG tabela 8.17: trgovski centri, storitvena dejavnost tD = 3000, tN = 2000.
        return ['podnevi' => 3000, 'ponoci' => 2000];
    }

    /**
     * @inheritDoc
     */
    public function referencnaOsvetlitevDelovnePovrsine(): int
    {
        // TSG tabela 11.5: projektirana osvetljenost delovne površine 750 lx.
        return 750;
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSRazsvetljava(Cona $cona): array
    {
        // TSG tabela 11.5: projektirana osvetljenost delovne površine 750 lx, FDS = 0,0 %.
        return $this->refRazsvetljava($cona, $this->referencnaOsvetlitevDelovnePovrsine());
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSPrezracevanja(Cona $cona): array
    {
        // TSG tabela 11.5: mehansko prezračevanje z vračanjem toplote (65 %), konstanten pretok, razred AB 3.
        return $this->refPrezracevanjeCentralno($cona);
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSOHT(Cona $cona): array
    {
        // TSG tabela 11.5 - Referenčni TSS v trgovskih stavbah (Tr-1):
        // - ogrevanje: centralni toplovodni sistem, plinski kondenzacijski kotel (105 %), dvocevni razvod 55/45 °C,
        //   ventilatorski konvektorji (4-cevni) s PI termostatskimi ventili z motornim pogonom;
        // - TSV: lokalni električni grelniki (10 l, 1 grelnik na 100 m2 Ause, 45/10 °C, ON/OFF);
        // - hlajenje: kompresorsko hlajenje z ohlajeno vodo 8/14 °C, COPref = 3,5, zunanji suh prenosnik toplote.
        return [
            $this->refToplovodniSistem($cona, 'konvektorji', 'elektricni'),
            $this->refHlajenjeHladnaVoda($cona),
        ];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSFotovoltaika(Cona $cona): array
    {
        // TSG tabela 11.5, vrstica OVE: površina fotonapetostnih modulov 0,04 x Ause, polikristalne Si celice.
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
