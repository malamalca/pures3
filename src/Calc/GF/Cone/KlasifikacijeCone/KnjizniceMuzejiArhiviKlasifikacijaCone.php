<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;
use App\Lib\Calc;

class KnjizniceMuzejiArhiviKlasifikacijaCone extends KlasifikacijaCone
{
    public string $code = 'Kn-1';

    public float $notranjaTOgrevanje = 22;
    public float $notranjaTHlajenje = 25;

    /**
     * @inheritDoc
     */
    public function izracunTSVZaMesec(int $mesec, Cona $cona): float
    {
        $stDni = Calc::steviloDni($mesec);

        if (isset($cona->TSV->steviloOseb)) {
            $energijaTSV = 0.4 * $cona->TSV->steviloOseb * $stDni;
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
        $stOseb = 0.06 * $cona->ogrevanaPovrsina;
        $kolicinaZrakaNaOsebo = 25.2; // [m3/h] [7 l/s * 3600s / 1000 l/m3]
        $faktorSocasneUporabe = 0.6;
        $dnevnaUporabaStavbe = 14; // [h]
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
        return ['podnevi' => 1800, 'ponoci' => 200];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSRazsvetljava(Cona $cona): array
    {
        // TSG tabela 11.7: projektirana osvetljenost delovne površine skladno s projektno dokumentacijo;
        // privzame se 300 lx, FDS = 0,0 %.
        return $this->refRazsvetljava($cona, $this->referencnaOsvetlitevDelovnePovrsine());
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSPrezracevanja(Cona $cona): array
    {
        // TSG tabela 11.7: mehansko prezračevanje z vračanjem toplote (65 %), konstanten pretok, razred AB 3.
        return $this->refPrezracevanjeCentralno($cona);
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSOHT(Cona $cona): array
    {
        // TSG tabela 11.7 - Referenčni TSS v muzejih, arhivih in knjižnicah (Kn-1):
        // - ogrevanje: centralni toplovodni sistem, plinski kondenzacijski kotel (105 %), dvocevni razvod 55/45 °C,
        //   ploščata ogrevala s termostatskimi ventili in PI-regulacijo;
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
        // TSG tabela 11.7, vrstica OVE: površina fotonapetostnih modulov 0,04 x Ause.
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
