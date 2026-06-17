<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;

class GostinskaKlasifikacijaCone extends KlasifikacijaCone
{
    public string $code = 'Go-1'; // ali Ho-1

    public float $notranjaTOgrevanje = 22;
    public float $notranjaTHlajenje = 25;

    /**
     * @inheritDoc
     */
    public function izracunTSVZaMesec(int $mesec, Cona $cona): float
    {
        $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

        switch ($this->code) {
            case 'Go-1':
                if (isset($cona->TSV->steviloOseb)) {
                    $energijaTSV = 1.5 * $cona->TSV->steviloOseb * $stDni;
                } else {
                    $energijaTSV = 1250 * $cona->ogrevanaPovrsina / 1000 * $stDni;
                }
                break;
            case 'Ho-1':
                if (!empty($cona->options['petZvezdic'])) {
                    if (isset($cona->TSV->steviloOseb)) {
                        $energijaTSV = 7.0 * $cona->TSV->steviloOseb * $stDni;
                    } else {
                        $energijaTSV = 580 * $cona->ogrevanaPovrsina / 1000 * $stDni;
                    }
                } else {
                    // velja za hotele s 3*
                    if (isset($cona->TSV->steviloOseb)) {
                        $energijaTSV = 1.5 * $cona->TSV->steviloOseb * $stDni;
                    } else {
                        $energijaTSV = 190 * $cona->ogrevanaPovrsina / 1000 * $stDni;
                    }
                }
                break;
            default:
                throw new \Exception('Neveljavna koda cone');
        }

        return $energijaTSV;
    }

    /**
     * @inheritDoc
     */
    public function kolicinaSvezegaZrakaZaPrezracevanje(Cona $cona): float
    {
        switch ($this->code) {
            case 'Go-1':
                $stOseb = 0.17 * $cona->ogrevanaPovrsina;
                $kolicinaZrakaNaOsebo = 25.2; // [m3/h] [7 l/s * 3600s / 1000 l/m3]
                $faktorSocasneUporabe = 0.46;
                $dnevnaUporabaStavbe = 20; // [h]
                $tedenskaUporabaStavbe = 7; // [dni/teden]
                break;
            case 'Ho-1':
                $stOseb = 0.05 * $cona->ogrevanaPovrsina;
                $kolicinaZrakaNaOsebo = 25.2; // [m3/h] [7 l/s * 3600s / 1000 l/m3]
                $faktorSocasneUporabe = 0.58;
                $dnevnaUporabaStavbe = 24; // [h]
                $tedenskaUporabaStavbe = 7; // [dni/teden]
                break;
            default:
                throw new \Exception('Neveljavna koda cone');
        }

        $volumenZraka = $kolicinaZrakaNaOsebo * $faktorSocasneUporabe * $stOseb *
        $dnevnaUporabaStavbe / 24 * $tedenskaUporabaStavbe / 7;

        return $volumenZraka;
    }

    /**
     * @inheritDoc
     */
    public function letnoSteviloUrDelovanjaRazsvetljave(): array
    {
        switch ($this->code) {
            case 'Go-1':
                return ['podnevi' => 1250, 'ponoci' => 1250];
            case 'Ho-1':
                return ['podnevi' => 3000, 'ponoci' => 2000];
            default:
                throw new \Exception('Neveljavna koda cone');
        }
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSRazsvetljava(Cona $cona): array
    {
        // TSG tabeli 11.2 in 11.3: projektirana osvetljenost delovne površine 500 lx, FDS = 0,0 %.
        return $this->refRazsvetljava($cona, 500);
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSPrezracevanja(Cona $cona): array
    {
        // TSG tabeli 11.2 in 11.3: mehansko prezračevanje z vračanjem toplote (65 %), konstanten pretok, razred AB 3.
        return $this->refPrezracevanjeCentralno($cona);
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSOHT(Cona $cona): array
    {
        // TSG tabela 11.2 (gostinske stavbe - restavracije, Go-1) in tabela 11.3 (hoteli, Ho-1):
        // - ogrevanje: centralni toplovodni sistem, plinski kondenzacijski kotel (105 %), dvocevni razvod 55/45 °C;
        //   Go-1: ploščata ogrevala s PI 1 K termostatskimi ventili; Ho-1: 4-cevni ventilatorski konvektorji;
        // - TSV: centralni sistem, kombinacija generatorja ogrevanja in solarnega toplotnega sistema;
        //   površina SSE 0,04 x Ause (Go-1) oziroma 0,08 x Ause (Ho-1);
        // - hlajenje: Go-1 multisplit COPref = 3,0; Ho-1 kompresorsko hlajenje z ohlajeno vodo 8/14 °C, COPref = 3,5.
        switch ($this->code) {
            case 'Go-1':
                return [
                    $this->refToplovodniSistem($cona, 'radiatorji', 'solarni', 0.04),
                    $this->refHlajenjeMultiSplit($cona),
                ];
            case 'Ho-1':
                return [
                    $this->refToplovodniSistem($cona, 'konvektorji', 'solarni', 0.08),
                    $this->refHlajenjeHladnaVoda($cona),
                ];
            default:
                throw new \Exception('Neveljavna koda cone');
        }
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSFotovoltaika(Cona $cona): array
    {
        // TSG tabeli 11.2 in 11.3: obnovljivi vir je solarni toplotni sistem za TSV (vključen v sistem OHT),
        // fotonapetostni sistem ni predviden.
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
