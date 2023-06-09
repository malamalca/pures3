<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<h1>Analiza TSS Razsvetljava "<?= h($sistem->id) ?>"</h1>

<p class="actions">
<a class="button" href="<?= App::url('/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>
<table border="1">
    <tr>
        <td colspan="1">Toplotna Cona:</td>
        <td colspan="3"><?= $sistem->idCone ?></td>
    </tr>
    <tr>
        <td>Faktor dnevne svetlobe:</td>
        <td>FDS<sub>T</sub></td>
        <td><?= $this->numFormat($sistem->faktorDnevneSvetlobe * 100, 1) ?></td>
        <td>%</td>
    </tr>
    <tr>
        <td>Specifična električna moč vgrajenih svetilk:</td>
        <td>P'<sub>L</sub></td>
        <td><?= $this->numFormat($sistem->mocSvetilk, 1) ?></td>
        <td>W/m2</td>
    </tr>
    <tr>
        <td>Letno št.ur razsvetljave - podnevi:</td>
        <td>t<sub>D</sub></td>
        <td><?= $this->numFormat($sistem->letnoUrPodnevi, 0) ?></td>
        <td>h/an</td>
    </tr>
    <tr>
        <td>Letno št.ur razsvetljave - ponoči:</td>
        <td>t<sub>D</sub></td>
        <td><?= $this->numFormat($sistem->letnoUrPonoci, 0) ?></td>
        <td>h/an</td>
    </tr>
</table>


<br />
<table border="1">
    <thead>
        <tr>
            <td></td>
            <?= implode(PHP_EOL, array_map(fn($mes) => '<td class="center">' . $mes . '</td>', Calc::MESECI)) ?>
            <td class="center">kWh/an</td>
        </tr>
    </thead>
    <tr>
        <td>E<sub>L,del,zn,m</sub>; E<sub>L,del,zn,an</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $sistem->potrebnaEnergija)) ?>
        <th class="right"><?= $this->numFormat(array_sum($sistem->potrebnaEnergija), 0) ?></th>
    </tr>
</table>