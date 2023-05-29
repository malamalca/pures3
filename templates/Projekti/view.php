<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<h1>Podatki o projektu "<?= h($splosniPodatki->stavba->naziv) ?>"</h1>

<p>
<a class="button" href="<?= App::url('/projekti/analiza/' . $projectName) ?>">Analiza GF</a>
<a class="button" href="<?= App::url('/izkazi/podrocjeGf/' . $projectName) ?>">Izkaz GF</a>
<a class="button" href="<?= App::url('/projekti/analizaSNES/' . $projectName) ?>">Analiza SNES</a>
<a class="button" href="<?= App::url('/izkazi/podrocjeSNES/' . $projectName) ?>">Izkaz sNES</a>
</p>
<p>
<?php
    foreach ($cone as $cona) {
?>
<a class="button" href="<?= App::url('/cone/analiza/' . $projectName . '/' . $cona->id) ?>">Analiza cone "<?= $cona->id ?>"</a>
<a class="button" href="<?= App::url('/cone/ovoj/' . $projectName . '/' . $cona->id) ?>">Analiza ovoja cone "<?= $cona->id ?>"</a>

<?php
    }
?>
</p>
<table border="1">
    <tr>
        <td colspan="2">Naziv projekta</td>
        <td colspan="2" class="left"><?= h($splosniPodatki->stavba->naziv) ?></td>
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
        <td rowspan="2">GK koordinate kraja</td>
        <td>GKX</td>
        <td class="center"><?= $this->numFormat($splosniPodatki->stavba->koordinate->X, 0) ?></td>
        <td></td>
    </tr>
    <tr>
        <td>GKY</td>
        <td class="center"><?= $this->numFormat($splosniPodatki->stavba->koordinate->Y, 0) ?></td>
        <td></td>
    </tr>
    <tr><td colspan="4"></tr>
</table>
