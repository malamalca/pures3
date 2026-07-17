<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;
use App\Lib\Calc;

class EnostanovanjskaKlasifikacijaCone extends KlasifikacijaCone
{
    public string $code = 'St-1';

    public float $notranjaTOgrevanje = 20;
    public float $notranjaTHlajenje = 26;

    // TSG tabela 11.1: referenčna temperatura TSV 45 °C/10 °C (enostanovanjske stavbe).
    // Posamezna cona lahko privzeto vrednost prepiše prek TSV->toplaVodaT / TSV->hladnaVodaT v vhodnih podatkih.
    public int $toplaVodaT = 45;
    public int $hladnaVodaT = 10;

    /**
     * @inheritDoc
     */
    public function izracunTSVZaMesec(int $mesec, Cona $cona): float
    {
        $toplaVodaT = $cona->TSV->toplaVodaT ?? $this->toplaVodaT;
        $hladnaVodaT = $cona->TSV->hladnaVodaT ?? $this->hladnaVodaT;

        if (empty($cona->TSV->steviloOseb)) {
            $steviloOseb = 0.025 * $cona->ogrevanaPovrsina;
            if ($steviloOseb > 1.75) {
                $steviloOseb = 1.75 + 0.3 * ($steviloOseb - 1.75);
            }
        } else {
            $steviloOseb = $cona->TSV->steviloOseb;
        }

        if (empty($cona->TSV->dnevnaKolicina)) {
            $dnevnaKolicina = min(40.71, 3.26 * $cona->ogrevanaPovrsina / $steviloOseb);
        } else {
            $dnevnaKolicina = $cona->TSV->dnevnaKolicina;
        }

        $stDni = Calc::steviloDni($mesec);

        $energijaTSV = 0.001 * $dnevnaKolicina * $steviloOseb * 4.2 / 3.6 *
            ($toplaVodaT - $hladnaVodaT) * $stDni -
            ($cona->vrnjeneIzgubeVTSV[$mesec] ?? 0);

        return $energijaTSV;
    }

    /**
     * @inheritDoc
     */
    public function kolicinaSvezegaZrakaZaPrezracevanje(Cona $cona): float
    {
        return $cona->ogrevanaPovrsina * 0.42 * 3600 / 1000;
    }

    /**
     * @inheritDoc
     */
    public function letnoSteviloUrDelovanjaRazsvetljave(): array
    {
        return ['podnevi' => 1820, 'ponoci' => 1680];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSRazsvetljava(Cona $cona): array
    {
        // TSG tabela 11.1: projektirana osvetljenost delovne površine 300 lx, FDS = 0,0 %.
        // Število ur (tD = 1820, tN = 1680) se prevzame iz letnoSteviloUrDelovanjaRazsvetljave() (tabela 8.17).
        return $this->refRazsvetljava($cona, $this->referencnaOsvetlitevDelovnePovrsine());
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSPrezracevanja(Cona $cona): array
    {
        /*
            - mehansko prezračevanje z dovodom in odvodom zraka in vračanjem toplote, konstanten pretok zraka,
            - tesnost stavbe n50 = 1,5 h1; za rekonstruirane stavbe 2,0 h1,
            - temperaturni izkoristek prenosnika za vračanje senzibilne toplote je 65 %
            - tesno razvodno omrežje,
            - tesno ohišje AHU,
            - ne upošteva se segrevanje zraka v ventilatorjih;
            - prezračevanje je uravnoteženo, skladno z razredom AB 3, tabela 13 v standardu SIST EN 167983,
            - pogoni SFP 3 dovod (0,211 W/(m3/h)), SFP 2 dovod (0,142 W/(m3/s)), (tabela 14 v standardu SIST EN 167983,
            - z upoštevanjem sestavnih komponent, ki so navedene v tabeli 15 v standardu SIST EN 167983),
            - hibridno prezračevanje se ne upošteva,
            - brez predogrevanja in predhlajenja zraka za prezračevanje.
        */
        $ret = new \stdClass();
        $ret->id = $cona->id;
        $ret->idCone = $cona->id;
        $ret->vrsta = 'centralni';
        $ret->razredH1H2 = true;
        $ret->mocSenzorjev = 0;
        $ret->razredH1H2 = true;

        $ret->odvod = new \stdClass();
        $ret->odvod->filter = 'hepa';
        $ret->dovod = new \stdClass();
        $ret->dovod->filter = 'hepa';

        $ret->volumenProjekt = $cona->netoProstornina / 2;

        return [$ret];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSOHT(Cona $cona): array
    {
        /*
        TSG tabela 11.1 - Referenčni TSS v referenčni stanovanjski stavbi:
        - kombiniran sistem toplovodnega ogrevanja in TSV s hranilnikom TSV, ki je ogrevan s solarnim toplotnim
          sistemom in dogrevan z generatorjem toplote ogrevalnega sistema;
        - generator toplote: plinski kondenzacijski kotel, izkoristek pri polni moči 105 % (spodnja kurilnost),
          znotraj toplotnega ovoja;
        - dvocevni razvod 55/45 °C, hidravlično uravnotežen; ploščata ogrevala s PI 1 K termostatskimi ventili;
        - SSE: površina 0,04 x Ause (St-1) oz. 0,03 x Ause (St-2) in 0,05 x Ause (St-3) - glej solarniFaktorSSE();
        - hlajenje (kadar je izvedeno v obravnavani stavbi): multisplit z direktnim uparjanjem, COPref = 3,0.
        */
        return [
            $this->refToplovodniSistem($cona, 'radiatorji', 'solarni', $this->solarniFaktorSSE()),
            $this->refHlajenjeMultiSplit($cona),
        ];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSFotovoltaika(Cona $cona): array
    {
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
