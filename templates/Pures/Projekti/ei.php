<?php
    use App\Core\App;
    use App\Calc\GF\Stavbe\Izbire\VrstaGradnje;
    use App\Calc\GF\Stavbe\Izbire\VrstaZahtevnosti;
    use App\Calc\GF\TSS\TSSVrstaEnergenta;

    use App\Calc\GF\Cone\Izbire\VrstaIzpostavljenostiFasad;
    use App\Calc\GF\Cone\Izbire\VrstaLegeStavbe;

    $this->layout = false;
    header('Content-Type: text/xml');
?>
<?xml version="1.0" encoding="utf-8"?>
<reiXmlPrenos>
    <program>drugo</program>
    <nazivStavbe><?= h($stavba->naziv) ?></nazivStavbe>
    <LokacijaX><?= $stavba->koordinate->X ?></LokacijaX>
    <LokacijaY><?= $stavba->koordinate->Y ?></LokacijaY>
    <VrstaStavbe><?= VrstaZahtevnosti::from($stavba->vrsta)->sifraEI() ?></VrstaStavbe>
    <VrstaGradnje><?= VrstaGradnje::from($stavba->tip)->sifraEI() ?></VrstaGradnje>
    <JavnaStavba><?= $stavba->javna ? 'true' : 'false' ?></JavnaStavba>
    <QNH><?= $this->numFormat($stavba->skupnaEnergijaOgrevanje, 0) ?></QNH>
    <QNC><?= $this->numFormat($stavba->skupnaEnergijaHlajenje, 0) ?></QNC>
    <RefKlima><?= $this->numFormat($stavba->skupnaEnergijaOgrevanje, 0) ?></RefKlima>
    <NetoPovrsina><?= $this->numFormat($stavba->ogrevanaPovrsina, 0) ?></NetoPovrsina>
    <BrutoVolumen><?= $this->numFormat($stavba->brutoProstornina, 0) ?></BrutoVolumen>
    <A><?= $this->numFormat($stavba->povrsinaOvoja, 0) ?></A>
    <Ht><?= $this->numFormat($stavba->specKoeficientTransmisijskihIzgub, 3, '.') ?></Ht>
    <f0><?= $this->numFormat($stavba->faktorOblike, 1, '.') ?></f0>
    <z><?= $this->numFormat($stavba->razmerjeTranspCelota, 3, '.') ?></z>
    <TP><?= $this->numFormat($okolje->temperaturniPrimanjkljaj, 0) ?></TP>
    <Qfaux>1</Qfaux>
    <QfhEnergenti>
    <?php
        if (!empty($stavba->energijaTSSPoEnergentih->ogrevanje)) {
            foreach (get_object_vars($stavba->energijaTSSPoEnergentih->ogrevanje) as $energentId => $energija) {
                $energent = TSSVrstaEnergenta::from($energentId);
    ?>
        <energent>
        <sifra><?= h($energent->sifraEI()) ?></sifra>
        <naziv><?= h($energent->naziv()) ?></naziv>
        <vrednost><?= $this->numFormat($energija, 1, '.') ?></vrednost>
        </energent>
    <?php
            }
        }
    ?>
    </QfhEnergenti>
    <QfcEnergenti>
    <?php
        if (!empty($stavba->energijaTSSPoEnergentih->hlajenje)) {
            foreach (get_object_vars($stavba->energijaTSSPoEnergentih->hlajenje) as $energentId => $energija) {
                $energent = TSSVrstaEnergenta::from($energentId);
    ?>
        <energent>
        <sifra><?= h($energent->sifraEI()) ?></sifra>
        <naziv><?= h($energent->naziv()) ?></naziv>
        <vrednost><?= $this->numFormat($energija, 1, '.') ?></vrednost>
        </energent>
    <?php
            }
        }
    ?>
    </QfcEnergenti>
    <QfvEnergenti>
    <?php
        if (!empty($stavba->energijaTSSPoEnergentih->prezracevanje)) {
            foreach (get_object_vars($stavba->energijaTSSPoEnergentih->prezracevanje) as $energentId => $energija) {
                $energent = TSSVrstaEnergenta::from($energentId);
    ?>
        <energent>
        <sifra><?= h($energent->sifraEI()) ?></sifra>
        <naziv><?= h($energent->naziv()) ?></naziv>
        <vrednost><?= $this->numFormat($energija, 1, '.') ?></vrednost>
        </energent>
    <?php
            }
        }
    ?>
    </QfvEnergenti>
    <QfwEnergenti>
    <?php
        if (!empty($stavba->energijaTSSPoEnergentih->tsv)) {
            foreach (get_object_vars($stavba->energijaTSSPoEnergentih->tsv) as $energentId => $energija) {
                $energent = TSSVrstaEnergenta::from($energentId);
    ?>
        <energent>
        <sifra><?= h($energent->sifraEI()) ?></sifra>
        <naziv><?= h($energent->naziv()) ?></naziv>
        <vrednost><?= $this->numFormat($energija, 1, '.') ?></vrednost>
        </energent>
    <?php
            }
        }
    ?>
    </QfwEnergenti>
    <QflEnergenti>
    <?php
        if (!empty($stavba->energijaTSSPoEnergentih->razsvetljava)) {
            foreach ($stavba->energijaTSSPoEnergentih->razsvetljava as $energentId => $energija) {
                $energent = TSSVrstaEnergenta::from($energentId);
    ?>
        <energent>
        <sifra><?= h($energent->sifraEI()) ?></sifra>
        <naziv><?= h($energent->naziv()) ?></naziv>
        <vrednost><?= $this->numFormat($energija, 1, '.') ?></vrednost>
        </energent>
    <?php
            }
        }
    ?>
    </QflEnergenti>
    <Ehund>0</Ehund>
    <Edhund>0</Edhund>
    <NetoVolumen><?= $this->numFormat($stavba->netoProstornina, 0) ?></NetoVolumen>
    <Hv><?= $this->numFormat($stavba->specVentilacijskeIzgube, 2, '.') ?></Hv>
    <Edel><?= $this->numFormat($stavba->neutezenaDovedenaEnergija, 0) ?></Edel>
    <EPren><?= $this->numFormat($stavba->obnovljivaPrimarnaEnergija, 0) ?></EPren>
    <EPnren><?= $this->numFormat($stavba->neobnovljivaPrimarnaEnergija, 0) ?></EPnren>
    <EPtot><?= $this->numFormat($stavba->skupnaPrimarnaEnergija, 0) ?></EPtot>
    <Eelexp><?= $this->numFormat($stavba->skupnaOddanaElektricnaEnergija, 0) ?></Eelexp>
    <Qexp><?= $this->numFormat($stavba->skupnaOddanaToplota, 0) ?></Qexp>
    <ROVE><?= $this->numFormat($stavba->ROVE, 1, '.') ?></ROVE>
    <ROVEXove><?= $this->numFormat($stavba->X_OVE, 1, '.') ?></ROVEXove>
    <ROVEYove><?= $this->numFormat($stavba->Y_ROVE, 1, '.') ?></ROVEYove>
    <Xs><?= $this->numFormat($stavba->X_s, 1, '.') ?></Xs>
    <Xp><?= $this->numFormat($stavba->X_p, 1, '.') ?></Xp>
    <Yhnd><?= $this->numFormat($stavba->Y_Hnd, 1, '.') ?></Yhnd>
    <SEPtot><?= $this->numFormat($stavba->specificnaPrimarnaEnergija, 1, '.') ?></SEPtot>
    <SEPtotkor><?= $this->numFormat($stavba->korigiranaSpecificnaPrimarnaEnergija, 1, '.') ?></SEPtotkor>
    <SEPtotdov><?= $this->numFormat($stavba->dovoljenaSpecificnaPrimarnaEnergija, 1, '.') ?></SEPtotdov>
    <SEPtotkordov><?= $this->numFormat($stavba->dovoljenaKorigiranaSpecificnaPrimarnaEnergija, 1, '.') ?></SEPtotkordov>
    <MCO2><?= $this->numFormat($stavba->izpustCO2, 0) ?></MCO2>
    <Xhtr><?= $this->numFormat($stavba->X_Htr, 1, '.') ?></Xhtr>
    <Xhnd><?= $this->numFormat($stavba->X_Hnd, 1, '.') ?></Xhnd>
    <VrstaModeliranja>1</VrstaModeliranja>
    <rei>
        <cone_num><?= $this->numFormat(count($stavba->cone), 0) ?></cone_num>
        <gradnja><?= $stavba->cone[0]->toplotnaKapaciteta <= 110000 ? 1 : 2 ?></gradnja>
        <lega_stavbe><?= VrstaLegeStavbe::from($stavba->cone[0]->infiltracija->lega)->sifraEI() ?></lega_stavbe>
        <zavetrovanost><?= VrstaIzpostavljenostiFasad::from($stavba->cone[0]->infiltracija->zavetrovanost)->sifraEI() ?></zavetrovanost>
        <cone/>
        <ogrevalni_sistemi/>
        <os_razvodi_ogr_stavbe/>
        <os_hranilniki_toplote/>
        <os_generatorji_toplote/>
        <daljinska_ogrevanja/>
        <toplotne_crpalke/>
        <hvac_sistemi/>
        <razvodi_tsv_stavbe/>
        <generatorji_toplote_tsv/>
        <sprejemniki_soncne_energ/>
        <fotovoltaicni_paneli/>
    </rei>
</reiXmlPrenos>