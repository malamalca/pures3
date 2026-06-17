<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;

class IzobrazevalnaKlasifikacijaCone extends KlasifikacijaCone
{
    public string $code = 'Iz-1';

    public float $notranjaTOgrevanje = 22;
    public float $notranjaTHlajenje = 25;

    /**
     * @inheritDoc
     */
    public function izracunTSVZaMesec(int $mesec, Cona $cona): float
    {
        $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

        if (!empty($cona->TSV->steviloOseb)) {
            $energijaTSV = 0.5 * $cona->TSV->steviloOseb * $stDni;
        } else {
            if (empty($cona->options['povrsinaUcilnic'])) {
                throw new \Exception('Površina učilnic ni podana, a je potrebna za izračun TSV.');
            }

            // v TSG je različno, če so tuši ali ne
            $faktorTusev = 170;
            if (!empty($cona->options['tusi'])) {
                $faktorTusev = 500;
            }

            $energijaTSV = $faktorTusev * $cona->options['povrsinaUcilnic'] / 1000 * $stDni;
        }

        return $energijaTSV;
    }

    /**
     * @inheritDoc
     */
    public function kolicinaSvezegaZrakaZaPrezracevanje(Cona $cona): float
    {
        $stOseb = 0.18 * $cona->ogrevanaPovrsina;
        $kolicinaZrakaNaOsebo = 25.2; // [m3/h] [7 l/s * 3600s / 1000 l/m3]
        $faktorSocasneUporabe = 0.5;
        $dnevnaUporabaStavbe = 11; // [h]
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
        // TSG tabela 11.8 - Referenčni TSS v izobraževalnih stavbah ter stavbah za kulturo in razvedrilo:
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
        // TSG tabela 11.8, vrstica OVE: površina fotonapetostnih modulov 0,04 x Ause;
        // ostale veličine iz tabele 11.4 (polikristalne Si celice, href = 0,18).
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
