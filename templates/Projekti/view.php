<?php
    use App\Lib\Calc;
?>
<h1>Podatki o projektu "<?= h($splosniPodatki->stavba->naziv) ?>"</h1>


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
