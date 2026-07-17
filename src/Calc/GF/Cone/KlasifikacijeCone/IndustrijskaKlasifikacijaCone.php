<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;
use App\Lib\Calc;

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
        $stDni = Calc::steviloDni($mesec);

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
    public function letnoSteviloUrDelovanjaRazsvetljave(): array
    {
        // TSG tabela 8.17: industrijske stavbe tD = 2500, tN = 1500.
        return ['podnevi' => 2500, 'ponoci' => 1500];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSRazsvetljava(Cona $cona): array
    {
        // TSG tabela 11.6: število ur in osvetljenost delovne površine skladno s projektno dokumentacijo;
        // privzame se 300 lx, FDS = 0,0 %.
        return $this->refRazsvetljava($cona, $this->referencnaOsvetlitevDelovnePovrsine());
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSPrezracevanja(Cona $cona): array
    {
        // TSG tabela 11.6: mehansko prezračevanje z vračanjem toplote (65 %), konstanten pretok;
        // količine zraka se prevzamejo iz projektne dokumentacije obravnavane stavbe.
        return $this->refPrezracevanjeCentralno($cona);
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSOHT(Cona $cona): array
    {
        // TSG tabela 11.6 - Referenčni TSS v industrijskih stavbah (In-1):
        // - ogrevanje: toplovodno ogrevanje, plinski kondenzacijski kotel (105 %); drugi elementi podsistemov
        //   po projektni dokumentaciji obravnavane stavbe (privzamejo se ploščata ogrevala);
        // - TSV: kot v projektu obravnavane stavbe (privzamejo se lokalni električni grelniki);
        // - hlajenje (kadar je predvideno): kompresorsko hlajenje z ohlajeno vodo 8/14 °C, COPref = 3,5,
        //   zunanji suh prenosnik toplote.
        return [
            $this->refToplovodniSistem($cona, 'radiatorji', 'elektricni'),
            $this->refHlajenjeHladnaVoda($cona),
        ];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSFotovoltaika(Cona $cona): array
    {
        // TSG tabela 11.6, vrstica OVE: površina fotonapetostnih modulov 0,04 x Ause.
        return $this->refFotovoltaika($cona, 0.04);
    }

    /**
     * @inheritDoc
     */
    public function export()
    {
        return $this->code;
    }
}
