<?php
    use App\Core\App;

    $this->layout = false;
    header('Content-Type: text/xml');
?>
<?xml version="1.0" encoding="utf-8"?>
<reiXmlPrenos>
    <program>PHPures3</program>
    <nazivStavbe><?= h($stavba->naziv) ?></nazivStavbe>
    <LokacijaX><?= $stavba->koordinate->X ?></LokacijaX>
    <LokacijaY><?= $stavba->koordinate->Y ?></LokacijaY>
    <VrstaStavbe>3</VrstaStavbe>
    <VrstaGradnje>1</VrstaGradnje>
    <JavnaStavba><?= $stavba->javna ? 'true' : 'false' ?></JavnaStavba>
    <QNH><?= $this->numFormat($stavba->skupnaEnergijaOgrevanje, 0) ?></QNH>
    <QNC><?= $this->numFormat($stavba->skupnaEnergijaHlajenje, 0) ?></QNC>
    <RefKlima>94534</RefKlima>
    <NetoPovrsina><?= $this->numFormat($stavba->ogrevanaPovrsina, 0) ?></NetoPovrsina>
    <BrutoVolumen><?= $this->numFormat($stavba->brutoProstornina, 0) ?></BrutoVolumen>
    <A><?= $this->numFormat($stavba->povrsinaOvoja, 0) ?></A>
    <Ht><?= $this->numFormat($stavba->specKoeficientTransmisijskihIzgub, 3, '.') ?></Ht>
    <f0><?= $this->numFormat($stavba->faktorOblike, 1, '.') ?></f0>
    <z><?= $this->numFormat($stavba->razmerjeTranspCelota, 3, '.') ?></z>
    <TP><?= $this->numFormat($okolje->temperaturniPrimanjkljaj, 0) ?></TP>
    <Qfaux>1</Qfaux>
    <QfhEnergenti>
        <energent>
        <sifra>energy_dt</sifra>
        <naziv>Daljinsko</naziv>
        <vrednost>71573.7</vrednost>
        </energent>
        <energent>
        <sifra>energy_e</sifra>
        <naziv>Elektrika</naziv>
        <vrednost>822.9</vrednost>
        </energent>
    </QfhEnergenti>
    <QfcEnergenti>
    </QfcEnergenti>
    <QfvEnergenti>
        <energent>
        <sifra>energy_e</sifra>
        <naziv>Elektrika</naziv>
        <vrednost>59799.3</vrednost>
        </energent>
    </QfvEnergenti>
    <QfwEnergenti>
        <energent>
        <sifra>energy_dt</sifra>
        <naziv>Daljinsko</naziv>
        <vrednost>349277.2</vrednost>
        </energent>
        <energent>
        <sifra>energy_e</sifra>
        <naziv>Elektrika</naziv>
        <vrednost>878.4</vrednost>
        </energent>
    </QfwEnergenti>
    <QflEnergenti>
        <energent>
        <sifra>energy_e</sifra>
        <naziv>Elektrika</naziv>
        <vrednost>49510.</vrednost>
        </energent>
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
        <cone_num>1</cone_num>
        <gradnja>2</gradnja>
        <lega_stavbe>3</lega_stavbe>
        <zavetrovanost>1</zavetrovanost>
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