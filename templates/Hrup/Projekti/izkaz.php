<?php
    use App\Core\App;

    use App\Calc\Hrup\ZunanjiHrup\Izbire\ObmocjeZascitePredHrupom;
    use App\Calc\Hrup\ZunanjiHrup\Izbire\VrstaKazalcevHrupa;
?>
<p class="actions">
    <a class="button" href="<?= App::url('/hrup/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>

<h1>Izkaz o zaščiti pred hrupom </h1>

<table cellpadding="3" width="100%">
    <tr class="title"><th colspan="2"><h2>Podatki o stavbi</h2></th></tr>
    <tr>
        <td class="w-40">Naziv:</td>
        <td class="w-60 strong"> <?= h($splosniPodatki->stavba->naziv) ?></td>
    </tr>
    <tr>
        <td class="w-40">Lokacija stavbe:</td>
        <td class="w-60 strong">
            <?= h(implode(', ', $splosniPodatki->stavba->parcele)) ?>
            k.o. <?= h($splosniPodatki->stavba->KO) ?></td>
    </tr>
    <tr>
        <td class="w-40">Investitor:</td>
        <td class="w-60 strong">
            <?= h(implode(PHP_EOL, array_map(fn($investitor) => implode(', ', array_filter([$investitor->naziv, $investitor->naslov])), $splosniPodatki->investitorji))) ?>
        </td>
    </tr>
    
    <tr>
        <td class="w-40">Klasifikacija stavbe (CC-SI):</td>
        <td class="w-60 strong"> <?= h($splosniPodatki->stavba->klasifikacija) ?></td>
    </tr>
    <tr>
        <td class="w-40">Odgovorni vodja projekta:</td>
        <td class="w-60 strong"> <?= h($splosniPodatki->vodjaProjektiranja) ?></td>
    </tr>
    <tr>
        <td class="w-40">Izdelovalec elaborata:</td>
        <td class="w-60 strong"> <?= h($splosniPodatki->izdelovalec) ?></td>
    </tr>
    <tr>
        <td class="w-40">Številka elaborata:</td>
        <td class="w-60 strong"> <?= h($splosniPodatki->stevilkaElaborata) ?></td>
    </tr>
    <tr>
        <td class="w-40">Datum izdelave dokumentacije:</td>
        <td class="w-60 strong"> <?= h($splosniPodatki->datum) ?></td>
    </tr>
</table>
<table cellpadding="3" width="70%">
    <tr>
        <td colspan="4">Elaborat izdelan:</td>
    </tr>
    <tr>
        <td class="w-10">&nbsp;</td>
        <td class="w-5 strong border center">x</td>
        <td>po smernici
    </tr>
    <tr>
        <td class="w-10">&nbsp;</td>
        <td class="w-5 strong border center">&nbsp;</td>
        <td>po zadnjem stanju tehnike
    </tr>
</table>

<table cellpadding="3" width="70%">
    <tr class="title"><th colspan="4"><h2>Zaščita pred hrupom v okolju</h2></th></tr>
    <tr>
        <td colspan="4">Izračun izveden na podlagi:</td>
    </tr>
    <tr>
        <td class="w-10">&nbsp;</td>
        <td class="w-5 strong border center">
            <?= VrstaKazalcevHrupa::from($splosniPodatki->zunanjiHrup->kazalciHrupa) == VrstaKazalcevHrupa::GledeNaObmocje ? 'x' : '&nbsp;' ?>
        </td>
        <td>mejnih ravni hrupa v okolju (preglednica 1 v tehnični smernici)
    </tr>
    <tr>
        <td class="w-10">&nbsp;</td>
        <td class="w-5 strong border center">
            <?= VrstaKazalcevHrupa::from($splosniPodatki->zunanjiHrup->kazalciHrupa) == VrstaKazalcevHrupa::IzmerjeniAliIzracunani ? 'x' : '&nbsp;' ?>
        </td>
        <td>izmerjenih ali izračunanih ravni hrupa v okolju
    </tr>
</table>
<p>Merodajni kazalci hrupa v okolju, uporabljeni v izračunu zvočne izolirnosti ovoja stavbe:</p>
<table border="2" cellpadding="3" width="50%">
<?php
    if (isset($splosniPodatki->zunanjiHrup->vrednostiHrupa)) {
?>
        <tr>
<?php
        foreach ($splosniPodatki->zunanjiHrup->vrednostiHrupa as $oznaka => $vrednost) {
?>
            <td class="w-50 center strong"><?= h($oznaka) ?> [dB(A)]</td>
<?php
        }
?>
        </tr>
        <tr>
<?php
        foreach ($splosniPodatki->zunanjiHrup->vrednostiHrupa as $oznaka => $vrednost) {
?>
            <td class="w-50 center strong"><?= $vrednost ?></td>
<?php
        }
?>
    </tr>
<?php
    } else {
?>
    <tr>
        <td class="w-50 center strong">Ldvn [dB(A)]</td>
        <td class="w-50 center strong">Lnoč [dB(A)]</td>
    </tr>
    <tr>
        <td class="w-50 center"><?= ObmocjeZascitePredHrupom::from($splosniPodatki->zunanjiHrup->obmocje)->kazalci('Ldvn') ?></td>
        <td class="w-50 center"><?= ObmocjeZascitePredHrupom::from($splosniPodatki->zunanjiHrup->obmocje)->kazalci('Lnoc') ?></td>
    </tr>
<?php
    }
?>
</table>

<br />
<table border="1" cellpadding="3" width="100%">
    <tr class="title"><th colspan="7"><h2>Zvočna izolacija ovoja stavbe</h2></th></tr>
    <tr>
        <td class="w-60 strong" colspan="4">&nbsp;</td>
        <td class="w-20 strong center">Načrtovani ukrepi</td>
        <td class="w-20 strong center" colspan="2">Izvedeni ukrepi</td>
    </tr>
    <tr>
        <td class="w-40 strong" colspan="2">Ločilni element ali prostor</td>
        <td class="w-20 strong center" colspan="2">Projektne vrednosti</td>
        <td class="w-20 strong center">Izračunane vrednosti</td>
        <td class="w-20 strong center" colspan="2">Izmerjene vrednosti</td>
    </tr>
    <tr>
        <td class="w-10">Oznaka / pozicija</td>
        <td class="w-30">Element ali sklop elementov</td>
        <td class="w-10 center" style="border-right: dashed 1px">Oznaka veličine (enota)</td>
        <td class="w-10 center">&nbsp;</td>
        <td class="w-20 center">&nbsp;</td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">Ustreza (da/ne)</td>
    </tr>
    <tr>
        <td class="w-100 strong" colspan="7">PROSTORI</td>
    </tr>
    <?php
        if (isset($prostori)) {
            foreach ($prostori as $prostor) {
    ?>
    <tr>
        <td class="w-10"><?= h($prostor->id) ?></td>
        <td class="w-30"><?= h($prostor->naziv) ?></td>
        <td class="w-10 center" style="border-right: dashed 1px" class="nowrap">
            L<sub>notri</sub>&nbsp;[dBA]<br />
            R'<sub>s,w</sub>&nbsp;[dB]
        </td>
        <td class="w-10 center strong">
            maks. <?= $this->numFormat($prostor->Lmax, 0) ?><br />
            min. <?= $this->numFormat($prostor->minRw, 0) ?>
        </td>
        <td class="w-20 center strong">
            <?= $this->numFormat($prostor->Lzunaj - $prostor->Rw, 0) ?><br />
            <?= $this->numFormat($prostor->Rw, 0) ?>
        </td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
    </tr>
    <?php
            }
        }
    ?>
    <tr>
        <td class="w-100 strong" colspan="7">ZUNANJI POKONČNI LOČILNI ELEMENTI</td>
    </tr>
    <?php
        $elementi = array_merge((array)$konstrukcije, (array)$oknaVrata);
        foreach ($elementi as $element) {
            if (isset($element->tip) && $element->tip == 'vertikalna') {
    ?>
    <tr>
        <td class="w-10"><?= h($element->id) ?></td>
        <td class="w-30"><?= h($element->naziv) ?></td>
        <td class="w-10 center" style="border-right: dashed 1px">R'<sub>w</sub> [dB]</td>
        <td class="w-10 center strong"><?= $this->numFormat($element->Rw, 0) ?></td>
        <td class="w-20 center strong"><?= $this->numFormat($element->Rw, 0) ?></td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
    </tr>
    <?php
            }
        }
    ?>
    <tr>
        <td class="w-100 strong" colspan="7">ZUNANJI VODORAVNI LOČILNI ELEMENTI</td>
    </tr>
    <?php
        foreach ($elementi as $element) {
            if (isset($element->tip) && $element->tip == 'horizontalna') {
    ?>
    <tr>
        <td class="w-10"><?= h($element->id) ?></td>
        <td class="w-30"><?= h($element->naziv) ?></td>
        <td class="w-10 center" style="border-right: dashed 1px">R'<sub>w</sub> [dBA]</td>
        <td class="w-10 center strong"><?= $this->numFormat($element->Rw, 0) ?></td>
        <td class="w-20 center strong"><?= $this->numFormat($element->Rw, 0) ?></td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
    </tr>
    <?php
            }
        }
    ?>
</table>


<h2>Zaščita pred hrupom v stavbi</h2>
<table border="1" cellpadding="3" width="100%">
    <tr class="title"><th colspan="7"><h3>Zvočna izolacija notranji ločilnih elementov</h3></th></tr>
    <tr>
        <td class="w-60 strong" colspan="4">&nbsp;</td>
        <td class="w-20 strong center">Načrtovani ukrepi</td>
        <td class="w-20 strong center" colspan="2">Izvedeni ukrepi</td>
    </tr>
    <tr>
        <td class="w-40 strong" colspan="2">Ločilni element ali prostor</td>
        <td class="w-20 strong center" colspan="2">Projektne vrednosti</td>
        <td class="w-20 strong center">Izračunane vrednosti</td>
        <td class="w-20 strong center" colspan="2">Izmerjene vrednosti</td>
    </tr>
    <tr>
        <td class="w-10">Oznaka / pozicija</td>
        <td class="w-30">Element ali sklop elementov</td>
        <td class="w-10 center" style="border-right: dashed 1px">Oznaka veličine</td>
        <td class="w-10 center">&nbsp;</td>
        <td class="w-20 center">&nbsp;</td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">Ustreza (da/ne)</td>
    </tr>
    <tr>
        <td class="w-100 strong" colspan="7">NOTRANJI POKONČNI LOČILNI ELEMENT (stene, stene z vrati ipd.)</td>
    </tr>
    <?php
        if (!empty($zracniHrup)) {
            foreach ($zracniHrup as $locilnaKonstrukcija) {
    ?>
    <tr>
        <td class="w-10"><?= h($locilnaKonstrukcija->id) ?></td>
        <td class="w-30"><?= h($locilnaKonstrukcija->naziv) ?></td>
        <td class="w-10 center" style="border-right: dashed 1px">R'<sub>w</sub> [dB]</td>
        <td class="w-10 center strong"><?= $this->numFormat($locilnaKonstrukcija->minRw, 0) ?></td>
        <td class="w-20 center strong"><?= $this->numFormat($locilnaKonstrukcija->Rw, 0) ?></td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
    </tr>
    <?php
            }
        } else {
    ?>
    <tr>
        <td class="w-10">&nbsp;</td>
        <td class="w-30">&nbsp;</td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
        <td class="w-20 center">&nbsp;</td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
    </tr>
    <?php
        }
    ?>
    <tr>
        <td class="w-100 strong" colspan="7">NOTRANJI VODORAVNI LOČILNI ELEMENT (medetažne konstrukcije, podesti, stopnice)</td>
    </tr>
    <?php
        if (!empty($udarniHrup)) {
            foreach ($udarniHrup as $locilnaKonstrukcija) {
    ?>
    <tr>
        <td class="w-10"><?= h($locilnaKonstrukcija->id) ?></td>
        <td class="w-30"><?= h($locilnaKonstrukcija->naziv) ?></td>
        <td class="w-10 center" style="border-right: dashed 1px">L<sub>n,w</sub> [dB]</td>
        <td class="w-10 center strong"><?= $this->numFormat($locilnaKonstrukcija->minLnw, 0) ?></td>
        <td class="w-20 center strong"><?= $this->numFormat($locilnaKonstrukcija->Lnw, 0) ?></td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
    </tr>
    <?php
            }
        } else {
    ?>
    <tr>
        <td class="w-10">&nbsp;</td>
        <td class="w-30">&nbsp;</td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
        <td class="w-20 center">&nbsp;</td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
    </tr>
    <?php
        }
    ?>
</table>

<table border="1" cellpadding="3" width="100%">
    <tr class="title"><th colspan="7"><h3>Odmevni hrup</h3></th></tr>
    <tr>
        <td class="w-60 strong" colspan="4">&nbsp;</td>
        <td class="w-20 strong center">Načrtovani ukrepi</td>
        <td class="w-20 strong center" colspan="2">Izvedeni ukrepi</td>
    </tr>
    <tr>
        <td class="w-40 strong" colspan="2">Ločilni element ali prostor</td>
        <td class="w-20 strong center" colspan="2">Projektne vrednosti</td>
        <td class="w-20 strong center">Izračunane vrednosti</td>
        <td class="w-20 strong center" colspan="2">Izmerjene vrednosti</td>
    </tr>
    <tr>
        <td class="w-10">Oznaka / pozicija</td>
        <td class="w-30">Element ali sklop elementov</td>
        <td class="w-10 center" style="border-right: dashed 1px">Oznaka veličine</td>
        <td class="w-10 center">&nbsp;</td>
        <td class="w-20 center">&nbsp;</td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">Ustreza (da/ne)</td>
    </tr>
    <?php
        if (!empty($odmevniHrup)) {
            foreach ($odmevniHrup as $line) {
    ?>
    <tr>
        <td class="w-10"><?= h($line->id) ?></td>
        <td class="w-30"><?= h($line->naziv) ?></td>
        <td class="w-10 center" style="border-right: dashed 1px"><?= $line->enota ?></td>
        <td class="w-10 center strong"><?= $this->numFormat($line->projektnaVrednost, 1) ?></td>
        <td class="w-20 center strong"><?= $this->numFormat($line->izracunanaVrednost, 1) ?></td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
    </tr>
    <?php
            }
        } else {
    ?>
    <tr>
        <td class="w-10">&nbsp;</td>
        <td class="w-30">&nbsp;</td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
        <td class="w-20 center">&nbsp;</td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
    </tr>
    <?php
        }
    ?>
</table>

<table border="1" cellpadding="3" width="100%">
    <tr class="title"><th colspan="7"><h3>Hrup obratovalne opreme</h3></th></tr>
    <tr>
        <td class="w-60 strong" colspan="4">&nbsp;</td>
        <td class="w-20 strong center">Načrtovani ukrepi</td>
        <td class="w-20 strong center" colspan="2">Izvedeni ukrepi</td>
    </tr>
    <tr>
        <td class="w-40 strong" colspan="2">Ločilni element ali prostor</td>
        <td class="w-20 strong center" colspan="2">Projektne vrednosti</td>
        <td class="w-20 strong center">Izračunane vrednosti</td>
        <td class="w-20 strong center" colspan="2">Izmerjene vrednosti</td>
    </tr>
    <tr>
        <td class="w-10">Oznaka / pozicija</td>
        <td class="w-30">Element ali sklop elementov</td>
        <td class="w-10 center" style="border-right: dashed 1px">Oznaka veličine</td>
        <td class="w-10 center">&nbsp;</td>
        <td class="w-20 center">&nbsp;</td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">Ustreza (da/ne)</td>
    </tr>
    <?php
        if (!empty($strojniHrup)) {
            foreach ($strojniHrup as $line) {
    ?>
    <tr>
        <td class="w-10"><?= h($line->id) ?></td>
        <td class="w-30"><?= h($line->naziv) ?></td>
        <td class="w-10 center" style="border-right: dashed 1px"><?= $line->enota ?></td>
        <td class="w-10 center strong"><?= $this->numFormat($line->projektnaVrednost, 0) ?></td>
        <td class="w-20 center strong"><?= $this->numFormat($line->izracunanaVrednost, 0) ?></td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
    </tr>
    <?php
            }
        } else {
    ?>
    <tr>
        <td class="w-10">&nbsp;</td>
        <td class="w-30">&nbsp;</td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
        <td class="w-20 center">&nbsp;</td>
        <td class="w-10 center" style="border-right: dashed 1px">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
    </tr>
    <?php
        }
    ?>
</table>
<br />
<table border="1" cellpadding="3" width="100%">
    <tr>
        <td class="w-100"><h2 style="margin: 0">Opombe</h2>(izdelovalca izkaza in merilca)</td>
    </tr>
    <tr>
        <td class="w-100"><br /><br /><br /></td>
    </tr>
</table>

<p>Podpis izdelovalca elaborata:<br /></p>
<br /><br />
<p>Podpis pooblaščenca akreditirane (pravne ali fizične) osebe:</p>

<p>Datum opravljanja meritev:</p>

<p>Podpis osebe, ki je opravljala meritve:</p>

<p>Podpis odgovornega nadzornika:</p>