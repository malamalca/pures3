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
                if (!empty($options->petZvezdic)) {
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
        }

        $volumenZraka = $kolicinaZrakaNaOsebo * $faktorSocasneUporabe * $stOseb *
        $dnevnaUporabaStavbe / 24 * $tedenskaUporabaStavbe / 7;

        return $volumenZraka;
    }

    /**
     * @inheritDoc
     */
    public function letnoSteviloUrDelovanjaRazsvetljave(Cona $cona): array
    {
        switch ($this->code) {
            case 'Go-1':
                return ['podnevi' => 1250, 'ponoci' => 1250];
            case 'Ho-1':
                return ['podnevi' => 3000, 'ponoci' => 2000];
        }
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSRazsvetljava(Cona $cona): array
    {
        /*
            - upoštevase število ur uporabe električne razsvetljave skladno s tabelo 8.17;
            - projektirana osvetljenost delovne površine 500 lx;
            - vgrajene so LEDsijalke s svetlobnim učinkom hL = 80 lm/W, po letu 2025 pa 95 lm/W;
            - faktor uporabe razsvetljave Fo = 1 (occupancy dependency factor);
            - faktor trajnosti svetilnosti svetilke Fc = 1 (constant illuminance dependency factor);
            - faktor vzdrževanja svetilk FMF = 1;
            - faktor oblike prostora k = 1;
            - faktor dnevne svetlobe FDS = 0,0 %;
            - faktor zmanjšanja projektne osvetlitve FCA kot pri obravnavani stavbi, upoštevajo se vrednosti iz tabele 8.23;
            - varnostne svetilke se ne upoštevajo v izračunu rabe energije za delovanje sistema razsvetljave.
        */

        $ret = new \stdClass();
        $ret->id = $cona->id;
        $ret->idCone = $cona->id;
        $ret->faktorOblike = 1;
        $ret->faktorDnevneSvetlobe = 0.0;

        $ret->letnoUrPodnevi = 1820;
        $ret->letnoUrPonoci = 1820;

        $ret->osvetlitevDelovnePovrsine = 500;
        $ret->ucinkovitostViraSvetlobe = 95;
        $ret->faktorPrisotnosti = 1;
        $ret->faktorVzdrzevanja = 1;
        $ret->faktorZmanjsanjaSvetlobnegaToka = 1; // temu v tabeli TSG rečejo tudi faktor trajnosti Fc
        $ret->faktorDnevneSvetlobe = 0.05;
        $ret->faktorZmanjsaneOsvetlitveDelovnePovrsine =
            $cona->razsvetljava->faktorZmanjsaneOsvetlitveDelovnePovrsine ?? 1;

        return [$ret];
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
    public function export()
    {
        return $this->code;
    }
}
