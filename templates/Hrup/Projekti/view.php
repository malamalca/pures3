<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<h1>Podatki o projektu "<?= h($splosniPodatki->stavba->naziv) ?>"</h1>

<p class="actions">
    <a class="button" href="<?= App::url('/hrup/projekti/porocilo/' . $projectId) ?>">Tehnično poročilo</a>
    <a class="button" href="<?= App::url('/hrup/projekti/konstrukcije/' . $projectId) ?>">Konstrukcije</a>
    <a class="button" href="<?= App::url('/hrup/projekti/izkaz/' . $projectId) ?>">Izkaz</a>
</p>
<?php
    if (!empty($prostori)) {
?>
<p class="actions">
<?php
        foreach ($prostori as $prostor) {
?>
    <a class="button" href="<?= App::url('/hrup/zunanjiHrup/view/' . $projectId . '/' . $prostor->id) ?>"><?= h($prostor->naziv) ?></a>
<?php
        }
?>
</p>
<?php
    }
?>
<?php
    if (!empty($udarniHrup)) {
?>
<p class="actions">
    Udarni hrup:
<?php
        foreach ($udarniHrup as $locilnaKonstrukcija) {
?>
    <a class="button" href="<?= App::url('/hrup/udarniHrup/view/' . $projectId . '/' . $locilnaKonstrukcija->id) ?>"><?= h($locilnaKonstrukcija->id . ' - ' . $locilnaKonstrukcija->naziv) ?></a>
<?php
        }
?>
</p>
<?php
    }
?>
<?php
    if (!empty($zracniHrup)) {
?>
<p class="actions">
    Hrup v zraku: 
<?php
        foreach ($zracniHrup as $locilnaKonstrukcija) {
?>
    <a class="button" href="<?= App::url('/hrup/zracniHrup/view/' . $projectId . '/' . $locilnaKonstrukcija->id) ?>"><?= h($locilnaKonstrukcija->id . ' - ' . $locilnaKonstrukcija->naziv) ?></a>
<?php
        }
?>
</p>
<?php
    }
?>
<table>
    <tr>
        <td colspan="2">Naziv projekta</td>
        <td colspan="2" class="left strong"><?= h($splosniPodatki->stavba->naziv) ?></td>
    </tr>
    <tr>
        <td colspan="2">Ulica, kraj</td>
        <td colspan="2" class="left"><?= h($splosniPodatki->stavba->lokacija) ?></td>
    </tr>
    <tr>
        <td colspan="2">Katastrska občina</td>
        <td colspan="2" class="left"><?= h($splosniPodatki->stavba->KO) ?></td>
    </tr>
    <tr>
        <td colspan="2">Parcele</td>
        <td colspan="2" class="left"><?= h(implode(', ', $splosniPodatki->stavba->parcele)) ?></td>
    </tr>

    <tr>
        <td colspan="2">Klasifikacija</td>
        <td colspan="2" class="left"><?= h($splosniPodatki->stavba->klasifikacija) ?></td>
    </tr>

    <tr>
        <td colspan="4">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="2">Odg. vodja projekta</td>
        <td colspan="2" class="left"><?= h($splosniPodatki->vodjaProjektiranja) ?></td>
    </tr>
    <tr>
        <td colspan="2">Izdelovalec elaborata</td>
        <td colspan="2" class="left"><?= h($splosniPodatki->izdelovalec) ?></td>
    </tr>
    <tr>
        <td colspan="2">Številka elaborata</td>
        <td colspan="2" class="left"><?= h($splosniPodatki->stevilkaElaborata) ?></td>
    </tr>
    <tr>
        <td colspan="2">Datum elaborata</td>
        <td colspan="2" class="left"><?= h($splosniPodatki->datum) ?></td>
    </tr>
</table>
