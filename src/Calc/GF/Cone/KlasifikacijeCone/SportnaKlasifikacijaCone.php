<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;

class SportnaKlasifikacijaCone extends KlasifikacijaCone
{
    public string $code = 'Sp-1';

    public float $notranjaTOgrevanje = 22;
    public float $notranjaTHlajenje = 25;

    /**
     * @inheritDoc
     */
    protected function solarniFaktorSSE(): float
    {
        // TSG tabela 11.10, vrstica OVE: površina SSE je 0,03 x Au.
        return 0.03;
    }

    /**
     * @inheritDoc
     */
    public function izracunTSVZaMesec(int $mesec, Cona $cona): float
    {
        // TSG tabela 8.11.1: stavbe za šport - 1,5 kWh na osebo; ploskovni (na m2) podatek ni opredeljen (n.a.),
        // zato je število oseb obvezen vhodni podatek.
        if (empty($cona->TSV->steviloOseb)) {
            throw new \Exception('Za izračun TSV športne cone (Sp-1) je potrebno število oseb.');
        }

        $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

        return 1.5 * $cona->TSV->steviloOseb * $stDni;
    }

    /**
     * @inheritDoc
     */
    public function kolicinaSvezegaZrakaZaPrezracevanje(Cona $cona): float
    {
        // TSG tabela 6.1.4: Sp-1 - 7 l/s na osebo, 0,06 oseb/m2, fu = 0,6, 16 h/dan, 7 dni/teden.
        $stOseb = 0.06 * $cona->ogrevanaPovrsina;
        $kolicinaZrakaNaOsebo = 25.2; // [m3/h] [7 l/s * 3600s / 1000 l/m3]
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
        // TSG tabela 8.17: športni objekti tD = 2000, tN = 2000.
        return ['podnevi' => 2000, 'ponoci' => 2000];
    }

    /**
     * @inheritDoc
     */
    public function referencnaOsvetlitevDelovnePovrsine(): int
    {
        // TSG tabela 11.10: projektirana osvetljenost delovne površine 500 lx.
        return 500;
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSRazsvetljava(Cona $cona): array
    {
        // TSG tabela 11.10: projektirana osvetljenost delovne površine 500 lx, FDS = 0,0 %.
        return $this->refRazsvetljava($cona, $this->referencnaOsvetlitevDelovnePovrsine());
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSPrezracevanja(Cona $cona): array
    {
        // TSG tabela 11.10: mehansko prezračevanje z vračanjem toplote (65 %), razred AB 3.
        return $this->refPrezracevanjeCentralno($cona);
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSOHT(Cona $cona): array
    {
        // TSG tabela 11.10 - Referenčni TSS v stavbah za šport (Sp-1):
        // - ogrevanje: centralni toplovodni sistem, plinski kondenzacijski kotel (105 %), dvocevni razvod 35/30 °C;
        //   po smernici 50 % ploskovno (podno) ogrevanje in 50 % sevala. Pomožni model podpira en končni prenosnik
        //   na sistem, zato je ogrevanje poenostavljeno modelirano kot sevala (ploščata ogrevala) pri režimu 35/30 °C;
        //   ploskovni del bi zahteval dodatne parametre (vrsta sistema, izolacija), ki jih smernica za referenco
        //   ne določa;
        // - TSV: centralni toplovodni sistem v kombinaciji s solarnim toplotnim sistemom, SSE 0,03 x Au;
        // - hlajenje: kompresorsko hlajenje z ohlajeno vodo 8/14 °C, COPref = 3,5, zunanji suh prenosnik toplote.
        return [
            $this->refToplovodniSistem($cona, 'radiatorji', 'solarni', $this->solarniFaktorSSE(), '35/30'),
            $this->refHlajenjeHladnaVoda($cona),
        ];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSFotovoltaika(Cona $cona): array
    {
        // TSG tabela 11.10, vrstica OVE: obnovljivi vir je solarni toplotni sistem za TSV (vključen v OHT),
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
