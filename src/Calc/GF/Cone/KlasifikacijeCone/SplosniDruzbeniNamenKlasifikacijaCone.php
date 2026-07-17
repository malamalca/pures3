<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;
use App\Lib\Calc;

class SplosniDruzbeniNamenKlasifikacijaCone extends KlasifikacijaCone
{
    public string $code = 'Sd-1';

    public float $notranjaTOgrevanje = 22;
    public float $notranjaTHlajenje = 25;

    /**
     * @inheritDoc
     */
    public function izracunTSVZaMesec(int $mesec, Cona $cona): float
    {
        $stDni = Calc::steviloDni($mesec);

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
    public function letnoSteviloUrDelovanjaRazsvetljave(): array
    {
        return ['podnevi' => 3000, 'ponoci' => 2000];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSRazsvetljava(Cona $cona): array
    {
        // TSG tabela 11.4 (Po-1 in Sd-1): projektirana osvetljenost delovne površine 300 lx, FDS = 0,0 %.
        return $this->refRazsvetljava($cona, $this->referencnaOsvetlitevDelovnePovrsine());
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSPrezracevanja(Cona $cona): array
    {
        // TSG tabela 11.4: mehansko prezračevanje z vračanjem toplote (65 %), konstanten pretok, razred AB 3.
        return $this->refPrezracevanjeCentralno($cona);
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSOHT(Cona $cona): array
    {
        // TSG tabela 11.4 - Referenčni TSS v poslovnih in upravnih stavbah ter stavbah splošnega družbenega
        // pomena (Po-1 in Sd-1):
        // - ogrevanje: centralni toplovodni sistem, plinski kondenzacijski kotel (105 %), dvocevni razvod 55/45 °C,
        //   ventilatorski konvektorji (4-cevni) s PI 1 K termostatskimi ventili;
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
        // TSG tabela 11.4, vrstica OVE: površina fotonapetostnih modulov 0,04 x Ause, polikristalne Si celice.
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
