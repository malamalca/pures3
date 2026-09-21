<?php
declare(strict_types=1);

namespace App\Test\Pures;

use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

class ShemeTest extends TestCase
{
    /**
     * Preveri json glede na shemo
     *
     * @param string $shema Ime sheme
     * @param string $json Json niz
     * @return bool
     */
    private function jeVeljaven(string $shema, string $json): bool
    {
        $data = json_decode($json);
        $schema = json_decode((string)file_get_contents(SCHEMAS . 'Pures' . DS . $shema . 'Schema.json'));

        $validator = new Validator();
        $validator->validate($data, $schema);

        return $validator->isValid();
    }

    /**
     * Podatki testnih projektov morajo ustrezati shemam
     *
     * @return void
     */
    public function testPodatkiTestnihProjektovUstrezajoShemam(): void
    {
        $primeri = [
            'prezracevanje' => glob(ROOT . DS . 'projects/Pures/*/podatki/TSS/prezracevanje.json') ?: [],
            'ogrevanje' => glob(ROOT . DS . 'projects/Pures/*/podatki/TSS/ogrevanje.json') ?: [],
        ];

        foreach ($primeri as $shema => $datoteke) {
            $this->assertNotEmpty($datoteke, sprintf('Ni testnih podatkov za shemo "%s".', $shema));
            foreach ($datoteke as $datoteka) {
                $this->assertTrue(
                    $this->jeVeljaven($shema, (string)file_get_contents($datoteka)),
                    sprintf('Datoteka "%s" ne ustreza shemi "%s".', $datoteka, $shema)
                );
            }
        }
    }

    /**
     * @dataProvider primeriPrezracevanje
     * @param string $opis Opis primera
     * @param string $json Json niz
     * @param bool $pricakovano Pričakovana veljavnost
     * @return void
     */
    public function testShemaPrezracevanja(string $opis, string $json, bool $pricakovano): void
    {
        $this->assertSame($pricakovano, $this->jeVeljaven('prezracevanje', $json), $opis);
    }

    /**
     * @dataProvider primeriOgrevanje
     * @param string $opis Opis primera
     * @param string $json Json niz
     * @param bool $pricakovano Pričakovana veljavnost
     * @return void
     */
    public function testShemaOgrevanja(string $opis, string $json, bool $pricakovano): void
    {
        $this->assertSame($pricakovano, $this->jeVeljaven('ogrevanje', $json), $opis);
    }

    /**
     * @dataProvider primeriSplosniPodatki
     * @param string $opis Opis primera
     * @param string $json Json niz
     * @param bool $pricakovano Pričakovana veljavnost
     * @return void
     */
    public function testShemaSplosnihPodatkov(string $opis, string $json, bool $pricakovano): void
    {
        $this->assertSame($pricakovano, $this->jeVeljaven('splosniPodatki', $json), $opis);
    }

    /**
     * @dataProvider primeriRazsvetljava
     * @param string $opis Opis primera
     * @param string $json Json niz
     * @param bool $pricakovano Pričakovana veljavnost
     * @return void
     */
    public function testShemaRazsvetljave(string $opis, string $json, bool $pricakovano): void
    {
        $this->assertSame($pricakovano, $this->jeVeljaven('razsvetljava', $json), $opis);
    }

    /**
     * @dataProvider primeriCone
     * @param string $opis Opis primera
     * @param string $json Json niz
     * @param bool $pricakovano Pričakovana veljavnost
     * @return void
     */
    public function testShemaCone(string $opis, string $json, bool $pricakovano): void
    {
        $this->assertSame($pricakovano, $this->jeVeljaven('cone', $json), $opis);
    }

    /**
     * @dataProvider primeriFotovoltaika
     * @param string $opis Opis primera
     * @param string $json Json niz
     * @param bool $pricakovano Pričakovana veljavnost
     * @return void
     */
    public function testShemaFotovoltaike(string $opis, string $json, bool $pricakovano): void
    {
        $this->assertSame($pricakovano, $this->jeVeljaven('fotovoltaika', $json), $opis);
    }

    /**
     * @dataProvider primeriNetransparentne
     * @param string $opis Opis primera
     * @param string $json Json niz
     * @param bool $pricakovano Pričakovana veljavnost
     * @return void
     */
    public function testShemaNetransparentnih(string $opis, string $json, bool $pricakovano): void
    {
        $this->assertSame($pricakovano, $this->jeVeljaven('netransparentne', $json), $opis);
    }

    /**
     * @dataProvider primeriOknaVrata
     * @param string $opis Opis primera
     * @param string $json Json niz
     * @param bool $pricakovano Pričakovana veljavnost
     * @return void
     */
    public function testShemaOkenInVrat(string $opis, string $json, bool $pricakovano): void
    {
        $this->assertSame($pricakovano, $this->jeVeljaven('oknavrata', $json), $opis);
    }

    /**
     * @return array
     */
    public static function primeriCone(): array
    {
        $cona = '"id":"C1","naziv":"Cona","klasifikacija":"St-1",'
            . '"prezracevanje":{"vrsta":"naravno","izmenjava":0.5},'
            . '"infiltracija":{"n50":3,"lega":%s,"zavetrovanost":%s}';
        $ovoj = ',"ovoj":{"netransparentneKonstrukcije":[{"idKonstrukcije":"S1","povrsina":10}],'
            . '"transparentneKonstrukcije":[{"idKonstrukcije":"O1","povrsina":2}]}';

        return [
            ['element ovoja brez id in idKonstrukcije', '[{' . sprintf($cona, '"naPodezelju"', '"izpostavljenaEnaFasada"') . ',"ovoj":{"transparentneKonstrukcije":[{"povrsina":2}]}}]', true],
            ['element ovoja z idKonstrukcije', '[{' . sprintf($cona, '"naPodezelju"', '"izpostavljenaEnaFasada"') . $ovoj . '}]', true],
            ['lega kot zaporedna številka', '[{' . sprintf($cona, '2', '1') . '}]', true],
            ['lega stavbe v gozdu', '[{' . sprintf($cona, '"stavbaVGozdu"', '"izpostavljenaEnaFasada"') . '}]', true],
            ['lega, ki je ni v VrstaLegeStavbe', '[{' . sprintf($cona, '"stavbaNaOtoku"', '"izpostavljenaEnaFasada"') . '}]', false],
            ['lega izven obsega zaporednih številk', '[{' . sprintf($cona, '8', '1') . '}]', false],
            ['lega 0', '[{' . sprintf($cona, '0', '1') . '}]', false],
            ['zavetrovanost izven obsega', '[{' . sprintf($cona, '1', '3') . '}]', false],
        ];
    }

    /**
     * @return array
     */
    public static function primeriFotovoltaika(): array
    {
        $osnova = '"id":"PV","idCone":"C1","vrsta":"monokristalne","povrsina":10';

        return [
            ['modul z naklonom in orientacijo', '[{' . $osnova . ',"naklon":30,"orientacija":"J"}]', true],
            ['vodoravni modul brez orientacije', '[{' . $osnova . ',"naklon":0,"orientacija":""}]', true],
            ['vodoravni modul z orientacijo', '[{' . $osnova . ',"naklon":0,"orientacija":"J"}]', false],
            ['nagnjen modul brez orientacije', '[{' . $osnova . ',"naklon":30,"orientacija":""}]', false],
        ];
    }

    /**
     * @return array
     */
    public static function primeriNetransparentne(): array
    {
        $kons = '"id":"S1","naziv":"Stena","vrsta":1,"materiali":';

        return [
            ['kataloški material z debelino', '[{' . $kons . '[{"sifra":"polnaOpeka1800","debelina":0.3}]}]', true],
            ['kataloški material brez debeline', '[{' . $kons . '[{"sifra":"polnaOpeka1800"}]}]', false],
            ['ročno vnesen material', '[{' . $kons . '[{"opis":"Opeka","debelina":0.3,"lambda":0.76,"difuzijskaUpornost":12}]}]', true],
        ];
    }

    /**
     * @return array
     */
    public static function primeriOknaVrata(): array
    {
        return [
            ['okno z Uw', '[{"id":"O1","naziv":"Okno","vrsta":0,"Uw":1.1}]', true],
            ['okno z Uf, Ug, g in Psi', '[{"id":"O1","naziv":"Okno","vrsta":0,"Uf":1.3,"Ug":0.9,"g":0.5,"Psi":0.04}]', true],
            ['okno z Uf, Ug in g brez Psi', '[{"id":"O1","naziv":"Okno","vrsta":0,"Uf":1.3,"Ug":0.9,"g":0.5}]', false],
            ['vrata z Ud', '[{"id":"V1","naziv":"Vrata","vrsta":2,"Ud":1.6}]', true],
        ];
    }

    /**
     * @return array
     */
    public static function primeriSplosniPodatki(): array
    {
        $stavba = '"stavba":{"naziv":"H","lokacija":"L","KO":"KO","parcele":["1"],'
            . '"koordinate":{"X":100000,"Y":450000},"klasifikacija":"11100",'
            . '"vrsta":"manjzahtevna","tip":"nova","javna":false}';

        return [
            ['datum z letnico', '{' . $stavba . ',"datum":"april 2023"}', true],
            ['datum brez letnice', '{' . $stavba . ',"datum":"april"}', false],
            ['datum z letnico izven obsega', '{' . $stavba . ',"datum":"1. 1. 1999"}', false],
            ['brez datuma', '{' . $stavba . '}', false],
        ];
    }

    /**
     * @return array
     */
    public static function primeriRazsvetljava(): array
    {
        $osnova = '"id":"R","idCone":"C1","faktorDnevneSvetlobe":2,"mocSvetilk":6';

        return [
            ['gnezden sklop varnostne razsvetljave', '[{' . $osnova . ',"varnostna":{"energijaZaPolnjenje":1,"energijaZaDelovanje":2}}]', true],
            ['brez varnostne razsvetljave', '[{' . $osnova . '}]', true],
            ['napačen tip energije varnostne razsvetljave', '[{' . $osnova . ',"varnostna":{"energijaZaPolnjenje":"1"}}]', false],
        ];
    }

    /**
     * @return array
     */
    public static function primeriPrezracevanje(): array
    {
        return [
            ['lokalni sistem z močjo ventilatorja', '[{"id":"P","idCone":"C1","vrsta":"lokalni","mocVentilatorja":0.05}]', true],
            ['centralni sistem s projektnim pretokom', '[{"id":"P","idCone":"C1","vrsta":"centralni","volumenProjekt":200}]', true],
            ['centralni sistem brez projektnega pretoka', '[{"id":"P","idCone":"C1","vrsta":"centralni"}]', false],
            ['lokalni sistem brez moči ventilatorja', '[{"id":"P","idCone":"C1","vrsta":"lokalni"}]', false],
            ['sistem brez šifre cone', '[{"id":"P","vrsta":"lokalni","mocVentilatorja":1}]', false],
            ['neveljavna vrsta sistema', '[{"id":"P","idCone":"C1","vrsta":"hibridni","volumenProjekt":200}]', false],
            ['neveljavna vrsta filtra', '[{"id":"P","idCone":"C1","vrsta":"centralni","volumenProjekt":200,"dovod":{"filter":"g4"}}]', false],
            ['neveljavno krmiljenje', '[{"id":"P","idCone":"C1","vrsta":"centralni","volumenProjekt":200,"krmiljenje":"avtomatika"}]', false],
        ];
    }

    /**
     * @return array
     */
    public static function primeriOgrevanje(): array
    {
        $generator = '"generatorji":[{"id":"G","vrsta":"elektricniGrelnik"}]';

        return [
            ['split sistem hlajenja z EER', '[{"id":"S","idCone":"C1","vrsta":"splitHlajenje","nazivnaMoc":5,"EER":3.2}]', true],
            ['split sistem hlajenja brez EER', '[{"id":"S","idCone":"C1","vrsta":"splitHlajenje","nazivnaMoc":5}]', false],
            ['sistem brez šifre cone', '[{"id":"S","vrsta":"toplovodni",' . $generator . '}]', false],
            ['neveljavna vrsta sistema', '[{"id":"S","idCone":"C1","vrsta":"toplozracen"}]', false],
            ['neveljaven energent', '[{"idCone":"C1","vrsta":"toplovodni","energent":"vodik",' . $generator . '}]', false],
            ['neposredno električni sistem brez nazivne moči', '[{"idCone":"C1","vrsta":"neposrednoElektricni","prenosniki":[{"id":"E","vrsta":"elektricnoOgrevalo"}]}]', false],
            ['sklop TSV brez režima', '[{"idCone":"C1","vrsta":"toplovodni","tsv":{"generatorji":["G"]},' . $generator . '}]', false],
            ['neveljaven režim', '[{"idCone":"C1","vrsta":"toplovodni","ogrevanje":{"rezim":"60/40"},' . $generator . '}]', false],
            ['plinski kotel brez tipa', '[{"idCone":"C1","vrsta":"toplovodni","generatorji":[{"id":"K","vrsta":"plinskiKotel","regulacija":"konstantnaTemperatura"}]}]', false],
            ['plinski kotel s tipom podpostaje', '[{"idCone":"C1","vrsta":"toplovodni","generatorji":[{"id":"K","vrsta":"plinskiKotel","tip":"toplovod","regulacija":"konstantnaTemperatura"}]}]', false],
            ['hladilni kompresor brez EER', '[{"idCone":"C1","vrsta":"hladilni","hlajenje":{"generatorji":["K"]},"generatorji":[{"id":"K","vrsta":"hladilniKompresor","nazivnaMoc":6}]}]', false],
            ['sončni sprejemniki brez tipa', '[{"idCone":"C1","vrsta":"toplovodni","generatorji":[{"id":"S","vrsta":"solarniPaneli","povrsina":4}]}]', false],
            ['toplotna črpalka voda/voda z neveljavno temperaturo vira', '[{"idCone":"C1","vrsta":"toplovodni","generatorji":[{"id":"T","vrsta":"TC_vodavoda","temperaturaVira":12}]}]', false],
            ['razvod ogrevanja brez opisa cevi', '[{"idCone":"C1","vrsta":"toplovodni",' . $generator . ',"razvodi":[{"id":"R","vrsta":"dvocevni"}]}]', false],
            ['solarni razvod brez šifre generatorja', '[{"idCone":"C1","vrsta":"toplovodni",' . $generator . ',"razvodi":[{"id":"R","vrsta":"solar"}]}]', false],
            ['neveljavna regulacija obtočne črpalke', '[{"idCone":"C1","vrsta":"toplovodni",' . $generator . ',"razvodi":[{"id":"R","vrsta":"hlajenje","crpalka":{"regulacija":"pametna"}}]}]', false],
            ['razvod TSV z vnesenim časom delovanja črpalke', '[{"idCone":"C1","vrsta":"toplovodni",' . $generator . ',"razvodi":[{"id":"R","vrsta":"toplavoda","ceviHorizontaliVodi":{},"ceviDvizniVodi":{},"ceviPrikljucniVodi":{},"crpalka":{"casDelovanja":6}}]}]', true],
            ['čas delovanja črpalke izven obsega', '[{"idCone":"C1","vrsta":"toplovodni",' . $generator . ',"razvodi":[{"id":"R","vrsta":"toplavoda","ceviHorizontaliVodi":{},"ceviDvizniVodi":{},"ceviPrikljucniVodi":{},"crpalka":{"casDelovanja":26}}]}]', false],
            ['radiatorji brez namestitve', '[{"idCone":"C1","vrsta":"toplovodni",' . $generator . ',"prenosniki":[{"id":"R","vrsta":"radiatorji"}]}]', false],
            ['ploskovna ogrevala brez izolacije', '[{"idCone":"C1","vrsta":"toplovodni",' . $generator . ',"prenosniki":[{"id":"P","vrsta":"ploskovnaOgrevala","sistem":"talno_mokri"}]}]', false],
            ['neveljavna regulacija temperature', '[{"idCone":"C1","vrsta":"toplovodni",' . $generator . ',"prenosniki":[{"id":"K","vrsta":"konvektorji","regulacijaTemperature":"pametna"}]}]', false],
            ['hranilnik brez volumna', '[{"idCone":"C1","vrsta":"toplovodni",' . $generator . ',"hranilniki":[{"id":"H","vrsta":"posrednoOgrevan"}]}]', false],
        ];
    }
}
