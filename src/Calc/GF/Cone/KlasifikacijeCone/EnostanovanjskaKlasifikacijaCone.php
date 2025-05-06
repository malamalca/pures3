<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;

class EnostanovanjskaKlasifikacijaCone extends KlasifikacijaCone
{
    public string $code = 'St-1';

    public float $notranjaTOgrevanje = 20;
    public float $notranjaTHlajenje = 26;

    public int $toplaVodaT = 42;
    public int $hladnaVodaT = 10;

    /**
     * @inheritDoc
     */
    public function izracunTSVZaMesec(int $mesec, Cona $cona): float
    {
        $toplaVodaT = $this->TSV->toplaVodaT ?? $this->toplaVodaT;
        $hladnaVodaT = $this->TSV->hladnaVodaT ?? $this->hladnaVodaT;

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

        $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

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
    public function letnoSteviloUrDelovanjaRazsvetljave(Cona $cona): array
    {
        return ['podnevi' => 1820, 'ponoci' => 1680];
    }

    /**
     * @inheritDoc
     */
    public function referencniTSSRazsvetljava(Cona $cona): array
    {
        /*
        - upošteva se število ur uporabe električne razsvetljave skladno s tabelo 8.17,
        - projektirana  osvetljenost delovne površine 300 lx,
        - vgrajene so LEDsijalke s svetlobnim učinkom hL = 80 lm/W, po letu 2025 pa 95 lm/W,
        - faktor uporabe razsvetljave Fo = 1 (occupancy dependency factor),
        - faktor trajnosti svetilnosti svetilke Fc = 1 (constant illuminance dependency factor);
        - faktor vzdrževanja svetilk FMF = 1,
        - faktor oblike prostora k = 1,
        - faktor dnevne svetlobe FDS = 0,0 %,
        - faktor zmanjšanja projektne osvetlitve FCA kot pri obravnavani stavbi, upoštevajo se vrednosti iz tabele 8.23,
        - varnostne svetilke se ne upoštevajo v izračunu rabe energije za delovanje sistema razsvetljave.
        */

        $ret = new \stdClass();
        $ret->id = $cona->id;
        $ret->idCone = $cona->id;
        $ret->faktorOblike = 1;
        $ret->faktorDnevneSvetlobe = 0.0;

        $ret->letnoUrPodnevi = 1820;
        $ret->letnoUrPonoci = 1820;

        $ret->osvetlitevDelovnePovrsine = 300;
        $ret->ucinkovitostViraSvetlobe = 95;
        $ret->faktorPrisotnosti = 1;
        $ret->faktorVzdrzevanja = 1;
        $ret->faktorZmanjsanjaSvetlobnegaToka = 1; // temu v tabeli TSG rečejo tudi faktor trajnosti Fc
        $ret->faktorDnevneSvetlobe = 0;
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
    public function referencniTSSOHT(Cona $cona): array
    {
        /*
        - kombiniran sistem toplovodnega ogrevanja in TSV s hranilnikom TSV, ki je ogrevan s solarnim toplotnim
          sistemom in dogrevan z generatorjem toplote ogrevalnega sistema;
        - generator toplote: plinski kondenzacijski kotel, moč, določena pri referenčnih robnih pogojih za referenčno
          stavbo, namestitev znotraj toplotnega ovoja, izkoristek pri polni moči 105 % (spodnja kurilnost);
          učinkovitost pri delni obremenitvi in dodatna raba energije za delovanje generatorja se določita po
          generičnem modelu;
        - hranilnik TSV, ogrevan z generatorjem toplote ogrevalnega sistema, velikost po projektu obravnavane
          stavbe oziroma 0,8 l x Ause (Ause ≤ 1000 m2) ali 0,6 l > Ause (Ause > 1000 m2), če v obravnavani stavbi hranilnik
          solarnega toplotnega sistema ni vgrajen; toplotne izgube hranilnika se določijo z razredom učinkovitosti
          A (EU uredba 811/2013 in 812/2013);
        - dvocevni razvod 55/45 °C, hidravlično uravnotežen, nameščen v stavbi; dolžina cevovodov sistema
          ogrevanja in TSV po projektu obravnavane stavbe; če podatkov za obravnavo stavbo ni mogoče pridobiti
          ali je v obravnavani stavbi vgrajen drugačen ogrevalni sistem (npr. podno ogrevanje), se uporabi
          poenostavljena metoda dolžine cevovodov, navedena v dodatku B v standardu SIST EN 153163, če so
          izpolnjeni pogoji iz točke B 2.2.4 tega standarda;
        - razvod TSV brez recirkulacije; temperatura TSV 45°C/10°C; v večstanovanjskih stavbah TSV 55°C/10°C;
        - toplotni šok se izvaja 1 uro dnevno med 3. in 4. uro, temperatura TSV 70 °C;
        - debelina toplotne izolacije cevovodov 40 mm (d < 22 mm), premer cevi (25 < d < 100 mm) 60 mm, 100 mm
          za večje premere cevi; obtočne črpalke z vzdrževanjem konstantne tlačne razlike (s frekvenčno regulacijo);
          toplotne izgube cevovodov se določijo skladno s točko 6.4.1 v standardu SIST EN 153163;
        - ploščata ogrevala, s PI 1 K termostatskimi ventili.
        */
        $ogrtsv = new \stdClass();
        $ogrtsv->id = $cona->id;
        $ogrtsv->idCone = $cona->id;
        $ogrtsv->vrsta = 'toplovodni';
        $ogrtsv->energent = 'zemeljskiPlin';
        $ogrtsv->generatorji = [];
        $ogrtsv->hranilniki = [];
        $ogrtsv->razvodi = [];
        $ogrtsv->prenosniki = [];

        $ogrtsv->ogrevanje = new \stdClass();
        $ogrtsv->ogrevanje->rezim = '55/45';
        $ogrtsv->ogrevanje->generatorji = ['KOTEL'];
        $ogrtsv->ogrevanje->razvodi = ['OGREVANJE'];
        $ogrtsv->ogrevanje->prenosniki = ['TALNO'];

        $generator = new \stdClass();
        $generator->id = 'KOTEL';
        $generator->vrsta = 'plinskiKotel';
        $generator->tip = 'kondenzacijski';
        $generator->regulacija = 'konstantnaTemperatura';
        $generator->izkoristekPolneObremenitve = 1.05;
        $ogrtsv->generatorji[] = $generator;

        $razvod = new \stdClass();
        $razvod->id = 'OGREVANJE';
        $razvod->vrsta = 'dvocevni';
        $razvod->idPrenosnika = 'TALNO';
        $razvod->ceviHorizontaliVodi = new \stdClass();
        $razvod->ceviDvizniVodi = new \stdClass();
        $razvod->ceviPrikljucniVodi = new \stdClass();
        $razvod->crpalka = new \stdClass();
        $razvod->crpalka->regulacija = 'zRegulacijo';
        $ogrtsv->razvodi[] = $razvod;

        $prenosnik = new \stdClass();
        $prenosnik->id = 'TALNO';
        $prenosnik->vrsta = 'ploskovnaOgrevala';
        $prenosnik->sistem = 'talno_mokri';
        $prenosnik->izolacija = '100%';
        $prenosnik->hidravlicnoUravnotezenje = 'staticnoDviznihVodov';
        $prenosnik->regulacijaTemperature = 'PI-krmilnik';
        $ogrtsv->prenosniki[] = $prenosnik;

        $ogrtsv->tsv = new \stdClass();
        $ogrtsv->tsv->rezim = '40/30';
        $ogrtsv->tsv->generatorji = ['SSE', 'KOTEL'];
        $ogrtsv->tsv->razvodi = ['TSV'];
        $ogrtsv->tsv->hranilniki = ['TSVH'];

        $razvodTSV = new \stdClass();
        $razvodTSV->id = 'TSV';
        $razvodTSV->vrsta = 'toplavoda';
        $razvodTSV->ceviHorizontaliVodi = new \stdClass();
        $razvodTSV->ceviDvizniVodi = new \stdClass();
        $razvodTSV->ceviPrikljucniVodi = new \stdClass();
        $ogrtsv->razvodi[] = $razvodTSV;

        $razvodSolar = new \stdClass();
        $razvodSolar->id = 'TSV';
        $razvodSolar->vrsta = 'solar';
        $razvodSolar->idGeneratorja = 'SSE';
        $ogrtsv->razvodi[] = $razvodSolar;

        $hranilnikTSV = new \stdClass();
        $hranilnikTSV->id = 'TSVH';
        $hranilnikTSV->vrsta = 'solarniPosrednoOgrevan';
        $hranilnikTSV->volumen = ($cona->ogrevanaPovrsina < 1000 ? 0.8 : 0.6) * $cona->ogrevanaPovrsina;
        $hranilnikTSV->istiProstorKotGrelnik = true;
        $hranilnikTSV->znotrajOvoja = true;
        $ogrtsv->hranilniki[] = $hranilnikTSV;

        /*
        - TSV se pripravlja v kombinaciji sistema ogrevanja in solarnega toplotnega sistema; ravni selektivni SSE,
          površina SSE je 0,04 x Ause (m2) (St1) oziroma 0,03 x Ause (m2) (St2) in 0,05 x Ause (m2) (St3);
        - prostornina hranilnika TSV je 50 x ASSE (l); to velja tudi, če je na obravnavani stavbi predvidena
          drugačna površina SSE ali hranilnik;
        - konstrukcijske veličine naprav solarnega ogrevalnega sistema se privzamejo iz priloge B v standardu
          SIST EN 15316-4-3, in sicer iz točke B.2 (tipične vrednosti koeficientov učinkovitosti sprejemnikov sončne
          energije; ho, a1, Khem,50°), pri čemer se privzamejo vrednosti h0 = 0,8, a1 = 3,5, Khem,50° = 0,94, hloop,hx 0,9,
          B.3 (moč obtočne črpalke 25 + 2 x ASSE,tot (W), kjer je ASSE,tot skupna površina sprejemnikov sončne energije
          v m2), B.6 (koeficient toplotnih izgub solarnega hranilnika toplote H_sto,ls 20 x ASSE,tot (W/K)),
          B.11 (toplotne izgube cevovodov solarnega sistema H_loop,ls 5 + 0,5 v A_SSE,tot (W/K)) in
          B.12 v standardu SIST EN 1531643 kot tipične vrednosti;
          projektna temperatura za določitev toplotnih izgub je 55 °C, elementi solarnega ogrevalnega sistema
          (razen SSE) so v prostoru s temperaturo 20 °C; vračljive toplotne izgube so 0;
        - SSE so usmerjeni proti jugu z naklonom 30°, niso senčeni.
        */

        $generatorSSE = new \stdClass();
        $generatorSSE->id = 'SSE';
        $generatorSSE->vrsta = 'solarniPaneli';
        $generatorSSE->tip = 'zastekljen';
        $generatorSSE->povrsina = 0.04 * $cona->ogrevanaPovrsina;
        $generatorSSE->orientacija = 'J';
        $generatorSSE->naklon = 30;
        $generatorSSE->crpaka = new \stdClass();
        $generatorSSE->crpaka->moc = 25 + 2 * 0.04 * $cona->ogrevanaPovrsina;
        $ogrtsv->generatorji[] = $generatorSSE;

        /*
        - multisplit sistem z direktnim uparjanjem, COPref = 3,0, toplotna moč, določena pri projektnih pogojih
          in referenčnih pogojih za referenčno stavbo;
        - upoštevajo se senčila, gsh = 0,15; algoritem Gglob,b > 300 W/m2;
        - nočno hlajenje s prezračevanjem ali drugi načini naravnega ali aktivnega naravnega hlajenja se ne upoštevajo;
        - TSS je vgrajen v referenčni stavbi, kadar je sistem izveden v obravnavani stavbi.
        */
        $hlajenje = new \stdClass();
        $hlajenje->id = 'HLA';
        $hlajenje->idCone = $cona->id;
        $hlajenje->vrsta = 'splitHlajenje';
        $hlajenje->regulacija = 'prilagodljivoDelovanje';
        $hlajenje->multiSplit = true;
        $hlajenje->EER = 3;

        return [$ogrtsv, $hlajenje];
    }

    /**
     * @inheritDoc
     */
    public function export()
    {
        return $this->code;
    }
}
