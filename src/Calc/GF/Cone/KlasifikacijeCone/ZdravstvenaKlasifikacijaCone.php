<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;

class ZdravstvenaKlasifikacijaCone extends KlasifikacijaCone
{
    public string $code = 'Bo-1';

    public float $notranjaTOgrevanje = 22;
    public float $notranjaTHlajenje = 25;

    /**
     * @inheritDoc
     */
    public function izracunTSVZaMesec(int $mesec, Cona $cona): float
    {
        // TSG tabela 8.11.1: stavbe za zdravstveno oskrbo - 530 Wh/(m2 d) oz. 8 kWh na bolniško posteljo.
        $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

        if (isset($cona->TSV->steviloOseb)) {
            $energijaTSV = 8.0 * $cona->TSV->steviloOseb * $stDni;
        } else {
            $energijaTSV = 530 * $cona->ogrevanaPovrsina / 1000 * $stDni;
        }

        return $energijaTSV;
    }

    /**
     * @inheritDoc
     */
    public function kolicinaSvezegaZrakaZaPrezracevanje(Cona $cona): float
    {
        // TSG tabela 6.1.4: Bo-1 - 8 l/s na osebo, 0,1 oseb/m2, fu = 0,56, 24 h/dan, 7 dni/teden.
        $stOseb = 0.1 * $cona->ogrevanaPovrsina;
        $kolicinaZrakaNaOsebo = 28.8; // [m3/h] [8 l/s * 3600s / 1000 l/m3]
        $faktorSocasneUporabe = 0.56;
        $dnevnaUporabaStavbe = 24; // [h]
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
        // TSG tabela 8.17: bolnišnice tD = 3000, tN = 2000.
        return ['podnevi' => 3000, 'ponoci' => 2000];
    }

    /**
     * @inheritDoc
     */
    public function referencnaOsvetlitevDelovnePovrsine(): int
    {
        // TSG tabela 11.9: projektirana osvetljenost delovne površine 500 lx.
        return 500;
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSRazsvetljava(Cona $cona): array
    {
        // TSG tabela 11.9: projektirana osvetljenost delovne površine 500 lx, FDS = 0,0 %.
        return $this->refRazsvetljava($cona, $this->referencnaOsvetlitevDelovnePovrsine());
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSPrezracevanja(Cona $cona): array
    {
        // TSG tabela 11.9: mehansko prezračevanje z vračanjem toplote (65 %), konstanten pretok, razred AB 3.
        return $this->refPrezracevanjeCentralno($cona);
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSOHT(Cona $cona): array
    {
        // TSG tabela 11.9 - Referenčni TSS v stavbah za zdravstveno oskrbo (Bo-1):
        // - kombiniran sistem ogrevanja in TSV s hranilnikom TSV, povezanim s solarnim toplotnim sistemom;
        //   plinski kondenzacijski kotel (105 %), dvocevni razvod 55/45 °C, ploščata ogrevala s PI 1 K ventili;
        //   TSV s cirkulacijo 55/10 °C, toplotni šok; SSE 0,04 x Ause;
        // - hlajenje: kompresorsko hlajenje z ohlajeno vodo 8/14 °C, COPref = 3,5, 2-cevni ventilatorski konvektorji.
        return [
            $this->refToplovodniSistem($cona, 'radiatorji', 'solarni', 0.04),
            $this->refHlajenjeHladnaVoda($cona),
        ];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSFotovoltaika(Cona $cona): array
    {
        // TSG tabela 11.9, vrstica OVE: obnovljivi vir je solarni toplotni sistem za TSV (vključen v OHT),
        // ločen fotonapetostni sistem ni predviden.
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
