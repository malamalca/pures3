<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\KlasifikacijeCone;

use App\Calc\GF\Cone\Cona;

abstract class KlasifikacijaCone
{
    public string $code;

    // Tabela 6.1.2:
    public float $notranjaTOgrevanje;
    public float $notranjaTHlajenje;

    public int $toplaVodaT = 42;
    public int $hladnaVodaT = 10;
    public ?\stdClass $options;

    /**
     * Vrne svetlobni izkoristek vira svetlobe hL (lm/W) za referenčno stavbo glede na leto projekta.
     * TSG (tabele 11.1-11.10): vgrajene so LED-sijalke s hL = 80 lm/W, po letu 2025 pa 95 lm/W.
     * Če leto ni znano (ali je izven smiselnega obsega), se privzame novejša vrednost (95 lm/W).
     *
     * @param int|null $leto Leto projekta (npr. iz splošnih podatkov)
     * @return int
     */
    public static function ucinkovitostViraSvetlobeZaLeto(?int $leto): int
    {
        if ($leto === null || $leto < 2000) {
            return 95;
        }

        return $leto > 2025 ? 95 : 80;
    }

    /**
     * Class Constructor
     *
     * @param string $code Zone Code
     * @param \stdClass|null $options Dodatne nastavitve
     * @return void
     */
    public function __construct(string $code, ?\stdClass $options)
    {
        $this->code = $code;
        $this->options = $options;
    }

    /**
     * Izračun energije za TSV za specifični mesec
     * TSG Tabela 8.11.1 - Potrebna toplota za TSV v nestanovanjskih energetsko manj zahtevnih stavbah
     *
     * @param int $mesec Številka meseca
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return float
     */
    abstract public function izracunTSVZaMesec(int $mesec, Cona $cona): float;

    /**
     * Izračun količine oz. izmenjave svežega zraka za prezračevanje
     * Tabela 6.1.4: Spremenljivke, ki se upoštevajo pri določitvi količine svežega zraka za prezračevanje
     * energetsko manj zahtevnih (nestanovanjskih) stavb
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return float
     */
    abstract public function kolicinaSvezegaZrakaZaPrezracevanje(Cona $cona): float;

    /**
     * Izračun letnega števila delovanja električne razsvetljave
     * Tabela 8.17: Letno število ur delovanja električne razsvetljave posamezno cono (zn) v
     *
     * @return array<int> [podnevi, ponoci]
     */
    abstract public function letnoSteviloUrDelovanjaRazsvetljave(): array;

    /**
     * Vrne lastnosti TSS za razsvetljavo referenčne stavbe
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return array
     */
    abstract public function referencniTSSRazsvetljava(Cona $cona): array;

    /**
     * Vrne lastnosti TSS za prezračevanje referenčne stavbe
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return array
     */
    abstract public function referencniTSSPrezracevanja(Cona $cona): array;

    /**
     * Vrne lastnosti TSS za OHT referenčne stavbe
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return array
     */
    abstract public function referencniTSSOHT(Cona $cona): array;

    /**
     * Vrne lastnosti TSS za fotovoltaiko referenčne stavbe
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return array
     */
    abstract public function referencniTSSFotovoltaika(Cona $cona): array;

    /**
     * Export za json
     *
     * @return mixed
     */
    abstract public function export();

    /**
     * Projektirana osvetljenost delovne površine referenčne stavbe (lx) za to klasifikacijo cone.
     * Privzeto 300 lx (TSG tabele 11.1-11.10); podrazredi z drugačno zahtevo vrednost prepišejo.
     * Uporablja se enotno v izračunu QH,nd/QC,nd (notranja bremena) in EP (referenčni TSS razsvetljave).
     *
     * @return int
     */
    public function referencnaOsvetlitevDelovnePovrsine(): int
    {
        return 300;
    }

    /**
     * Faktor površine sprejemnikov sončne energije (SSE) glede na uporabno površino cone.
     * Privzeto 0,04 x Ause (TSG tabela 11.1, vrstica OVE). Podrazredi lahko vrednost prepišejo.
     *
     * @return float
     */
    protected function solarniFaktorSSE(): float
    {
        return 0.04;
    }

    /**
     * Zgradi referenčni toplovodni sistem (ogrevanje + TSV) skladno s smernico TSG-1-004, poglavje 11.
     * Generator ogrevanja je vedno plinski kondenzacijski kotel (izkoristek 105 %), dvocevni razvod 55/45 °C.
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @param string $emitter Vrsta končnega prenosnika ('radiatorji' za ploščata ogrevala ali 'konvektorji')
     * @param string $tsvNacin Način priprave TSV ('elektricni' za lokalne grelnike ali 'solarni')
     * @param float|null $sseFaktor Faktor površine SSE (le pri solarni TSV); privzeto solarniFaktorSSE()
     * @param string $rezim Temperaturni režim razvoda ogrevanja (privzeto '55/45'; npr. '35/30' za šport)
     * @return \stdClass
     */
    protected function refToplovodniSistem(
        Cona $cona,
        string $emitter,
        string $tsvNacin,
        ?float $sseFaktor = null,
        string $rezim = '55/45'
    ): \stdClass {
        $sistem = new \stdClass();
        $sistem->id = $cona->id;
        $sistem->idCone = $cona->id;
        $sistem->vrsta = 'toplovodni';
        $sistem->energent = 'zemeljskiPlin';
        $sistem->generatorji = [];
        $sistem->hranilniki = [];
        $sistem->razvodi = [];
        $sistem->prenosniki = [];

        $sistem->ogrevanje = new \stdClass();
        $sistem->ogrevanje->rezim = $rezim;
        $sistem->ogrevanje->generatorji = ['KOTEL'];
        $sistem->ogrevanje->razvodi = ['OGREVANJE'];
        $sistem->ogrevanje->prenosniki = ['OGREVALA'];

        $generator = new \stdClass();
        $generator->id = 'KOTEL';
        $generator->vrsta = 'plinskiKotel';
        $generator->tip = 'kondenzacijski';
        $generator->regulacija = 'konstantnaTemperatura';
        $generator->izkoristekPolneObremenitve = 1.05;
        $sistem->generatorji[] = $generator;

        $razvod = new \stdClass();
        $razvod->id = 'OGREVANJE';
        $razvod->vrsta = 'dvocevni';
        $razvod->idPrenosnika = 'OGREVALA';
        $razvod->ceviHorizontaliVodi = new \stdClass();
        $razvod->ceviDvizniVodi = new \stdClass();
        $razvod->ceviPrikljucniVodi = new \stdClass();
        $razvod->crpalka = new \stdClass();
        $razvod->crpalka->regulacija = 'zRegulacijo';
        $sistem->razvodi[] = $razvod;

        $prenosnik = new \stdClass();
        $prenosnik->id = 'OGREVALA';
        $prenosnik->vrsta = $emitter;
        if ($emitter === 'radiatorji') {
            // ploščata ogrevala ob zunanji steni (pod oknom), PI 1 K termostatski ventili
            $prenosnik->namestitev = 'zunanjeStene';
        }
        $prenosnik->hidravlicnoUravnotezenje = 'staticnoDviznihVodov';
        $prenosnik->regulacijaTemperature = 'PI-krmilnik';
        $sistem->prenosniki[] = $prenosnik;

        if ($tsvNacin === 'solarni') {
            $this->dodajSolarniTSV($cona, $sistem, $sseFaktor ?? $this->solarniFaktorSSE());
        } else {
            $this->dodajElektricniTSV($cona, $sistem);
        }

        return $sistem;
    }

    /**
     * Dodaj podsistem TSV z lokalnimi električnimi grelniki (TSG: 10 l, 1 grelnik na 100 m2 Ause).
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @param \stdClass $sistem Toplovodni sistem, ki se mu doda TSV
     * @return void
     */
    protected function dodajElektricniTSV(Cona $cona, \stdClass $sistem): void
    {
        $steviloGrelnikov = max(1, (int)round($cona->ogrevanaPovrsina / 100));

        $sistem->tsv = new \stdClass();
        $sistem->tsv->rezim = '40/30';
        $sistem->tsv->generatorji = ['ELGRELNIK'];
        $sistem->tsv->razvodi = ['TSV'];
        $sistem->tsv->hranilniki = ['TSVH'];

        $generator = new \stdClass();
        $generator->id = 'ELGRELNIK';
        $generator->vrsta = 'elektricniGrelnik';
        $generator->nazivnaMoc = 2;
        $generator->stevilo = $steviloGrelnikov;
        $generator->znotrajOvoja = true;
        $sistem->generatorji[] = $generator;

        $razvod = new \stdClass();
        $razvod->id = 'TSV';
        $razvod->vrsta = 'toplavoda';
        $razvod->ceviHorizontaliVodi = new \stdClass();
        $razvod->ceviDvizniVodi = new \stdClass();
        $razvod->ceviPrikljucniVodi = new \stdClass();
        $sistem->razvodi[] = $razvod;

        $hranilnik = new \stdClass();
        $hranilnik->id = 'TSVH';
        $hranilnik->vrsta = 'neposrednoOgrevan';
        $hranilnik->volumen = 10 * $steviloGrelnikov;
        $hranilnik->znotrajOvoja = true;
        $sistem->hranilniki[] = $hranilnik;
    }

    /**
     * Dodaj podsistem TSV s solarnim toplotnim sistemom in dogrevanjem prek generatorja ogrevanja (KOTEL).
     * Konstrukcijske veličine SSE in hranilnika skladno s TSG tabelo 11.1, vrstica OVE.
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @param \stdClass $sistem Toplovodni sistem, ki se mu doda TSV
     * @param float $sseFaktor Faktor površine SSE (x Ause)
     * @return void
     */
    protected function dodajSolarniTSV(Cona $cona, \stdClass $sistem, float $sseFaktor): void
    {
        $sistem->tsv = new \stdClass();
        $sistem->tsv->rezim = '40/30';
        $sistem->tsv->generatorji = ['SSE', 'KOTEL'];
        $sistem->tsv->razvodi = ['TSV'];
        $sistem->tsv->hranilniki = ['TSVH'];

        $razvodTSV = new \stdClass();
        $razvodTSV->id = 'TSV';
        $razvodTSV->vrsta = 'toplavoda';
        $razvodTSV->ceviHorizontaliVodi = new \stdClass();
        $razvodTSV->ceviDvizniVodi = new \stdClass();
        $razvodTSV->ceviPrikljucniVodi = new \stdClass();
        $sistem->razvodi[] = $razvodTSV;

        $razvodSolar = new \stdClass();
        $razvodSolar->id = 'TSV';
        $razvodSolar->vrsta = 'solar';
        $razvodSolar->idGeneratorja = 'SSE';
        $sistem->razvodi[] = $razvodSolar;

        $hranilnik = new \stdClass();
        $hranilnik->id = 'TSVH';
        $hranilnik->vrsta = 'solarniPosrednoOgrevan';
        // TSG tabela 11.1, vrstica OVE: prostornina hranilnika TSV je 50 x ASSE (l), kjer je ASSE = $sseFaktor x Ause.
        $hranilnik->volumen = 50 * $sseFaktor * $cona->ogrevanaPovrsina;
        $hranilnik->istiProstorKotGrelnik = true;
        $hranilnik->znotrajOvoja = true;
        $sistem->hranilniki[] = $hranilnik;

        $generatorSSE = new \stdClass();
        $generatorSSE->id = 'SSE';
        $generatorSSE->vrsta = 'solarniPaneli';
        $generatorSSE->tip = 'zastekljen';
        $generatorSSE->povrsina = $sseFaktor * $cona->ogrevanaPovrsina;
        $generatorSSE->orientacija = 'J';
        $generatorSSE->naklon = 30;
        $generatorSSE->crpaka = new \stdClass();
        $generatorSSE->crpaka->moc = 25 + 2 * $sseFaktor * $cona->ogrevanaPovrsina;
        $sistem->generatorji[] = $generatorSSE;
    }

    /**
     * Referenčni hladilni sistem: multisplit z direktnim uparjanjem, COPref = 3,0 (TSG).
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return \stdClass
     */
    protected function refHlajenjeMultiSplit(Cona $cona): \stdClass
    {
        $hlajenje = new \stdClass();
        $hlajenje->id = 'HLA';
        $hlajenje->idCone = $cona->id;
        $hlajenje->vrsta = 'splitHlajenje';
        $hlajenje->regulacija = 'prilagodljivoDelovanje';
        $hlajenje->multiSplit = true;
        $hlajenje->EER = 3;

        return $hlajenje;
    }

    /**
     * Referenčni hladilni sistem: kompresorsko hlajenje z ohlajeno vodo 8/14 °C, COPref = 3,5,
     * zunanji suh (zračno hlajen) prenosnik toplote, 4-cevni ventilatorski konvektorji (TSG).
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return \stdClass
     */
    protected function refHlajenjeHladnaVoda(Cona $cona): \stdClass
    {
        $hlajenje = new \stdClass();
        $hlajenje->id = 'HLA';
        $hlajenje->idCone = $cona->id;
        $hlajenje->vrsta = 'hladilni';
        $hlajenje->tip = 'zracnoHlajen';
        $hlajenje->energent = 'elektrika';
        $hlajenje->generatorji = [];
        $hlajenje->razvodi = [];
        $hlajenje->prenosniki = [];

        $hlajenje->hlajenje = new \stdClass();
        $hlajenje->hlajenje->generatorji = ['KOMPRESOR'];
        $hlajenje->hlajenje->razvodi = ['RAZVODHLAJENJA'];
        $hlajenje->hlajenje->prenosniki = ['HLADILNIKONVEKTORJI'];

        // moč, določena pri projektnih in referenčnih pogojih za referenčno stavbo
        $nazivnaMoc = ($cona->specTransmisijskeIzgube + $cona->Hve_hlajenje) *
            (35 - $cona->notranjaTHlajenje) / 1000;

        $generator = new \stdClass();
        $generator->id = 'KOMPRESOR';
        $generator->vrsta = 'hladilniKompresor';
        $generator->vrstaHlajenja = 'zracno';
        $generator->vrstaKompresorja = 'spiralni';
        $generator->vrstaRegulacije = 'prilagodljivo';
        $generator->nazivnaMoc = $nazivnaMoc;
        $generator->EER = 3.5;
        $hlajenje->generatorji[] = $generator;

        $razvod = new \stdClass();
        $razvod->id = 'RAZVODHLAJENJA';
        $razvod->vrsta = 'hlajenje';
        $razvod->idPrenosnika = 'HLADILNIKONVEKTORJI';
        $razvod->crpalka = new \stdClass();
        $hlajenje->razvodi[] = $razvod;

        $prenosnik = new \stdClass();
        $prenosnik->id = 'HLADILNIKONVEKTORJI';
        $prenosnik->vrsta = 'hladilniStropniKonvektor';
        $prenosnik->hidravlicnoUravnotezenje = 'staticnoDviznihVodov';
        $prenosnik->regulacijaTemperature = 'PI-krmilnik';
        $hlajenje->prenosniki[] = $prenosnik;

        return $hlajenje;
    }

    /**
     * Referenčni TSS za razsvetljavo (TSG, npr. tabela 11.4). Letno število ur se prevzame iz
     * letnoSteviloUrDelovanjaRazsvetljave(), faktorji uporabe/trajnosti/vzdrževanja so 1.
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @param int $osvetlitevDelovnePovrsine Projektirana osvetljenost delovne površine [lx]
     * @param float $faktorDnevneSvetlobe Faktor dnevne svetlobe FDS (privzeto 0,0)
     * @return array
     */
    protected function refRazsvetljava(
        Cona $cona,
        int $osvetlitevDelovnePovrsine,
        float $faktorDnevneSvetlobe = 0.0
    ): array {
        $ur = $this->letnoSteviloUrDelovanjaRazsvetljave();

        $ret = new \stdClass();
        $ret->id = $cona->id;
        $ret->idCone = $cona->id;
        $ret->faktorOblike = 1;

        $ret->letnoUrPodnevi = $ur['podnevi'];
        $ret->letnoUrPonoci = $ur['ponoci'];

        $ret->osvetlitevDelovnePovrsine = $osvetlitevDelovnePovrsine;
        $ret->ucinkovitostViraSvetlobe = self::ucinkovitostViraSvetlobeZaLeto($cona->options['leto'] ?? null);
        $ret->faktorPrisotnosti = 1;
        $ret->faktorVzdrzevanja = 1;
        $ret->faktorZmanjsanjaSvetlobnegaToka = 1; // faktor trajnosti Fc
        $ret->faktorDnevneSvetlobe = $faktorDnevneSvetlobe;
        $ret->faktorZmanjsaneOsvetlitveDelovnePovrsine =
            $cona->razsvetljava->faktorZmanjsaneOsvetlitveDelovnePovrsine ?? 1;

        return [$ret];
    }

    /**
     * Referenčni TSS za prezračevanje: centralno mehansko z vračanjem toplote (izkoristek 65 %),
     * konstanten pretok, razred AB 3 (TSG).
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @return array
     */
    protected function refPrezracevanjeCentralno(Cona $cona): array
    {
        $ret = new \stdClass();
        $ret->id = $cona->id;
        $ret->idCone = $cona->id;
        $ret->vrsta = 'centralni';
        $ret->razredH1H2 = true;
        $ret->mocSenzorjev = 0;

        $ret->odvod = new \stdClass();
        $ret->odvod->filter = 'hepa';
        $ret->dovod = new \stdClass();
        $ret->dovod->filter = 'hepa';

        $ret->volumenProjekt = $cona->netoProstornina / 2;

        return [$ret];
    }

    /**
     * Referenčni fotonapetostni sistem: površina 0,04 x Ause, jug, naklon 30°, brez senčenja,
     * brez oddaje v omrežje (fmatch = 1) (TSG, npr. tabela 11.4).
     *
     * @param \App\Calc\GF\Cone\Cona $cona Cona
     * @param float $faktor Faktor površine modulov (x Ause)
     * @param string $celice Vrsta sončnih celic
     * @return array
     */
    protected function refFotovoltaika(Cona $cona, float $faktor = 0.04, string $celice = 'polikristalne'): array
    {
        $ret = new \stdClass();
        $ret->id = $cona->id;
        $ret->idCone = $cona->id;
        $ret->vrsta = $celice;
        $ret->povrsina = $faktor * $cona->ogrevanaPovrsina;
        $ret->orientacija = 'J';
        $ret->naklon = 30;
        $ret->sencenje = false;
        $ret->vgradnja = 'dobroPrezracevani';
        $ret->kontrolniFaktor = 1;
        $ret->oddajaVOmrezje = false;

        return [$ret];
    }
}
