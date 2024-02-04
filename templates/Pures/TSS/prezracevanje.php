<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<p class="actions">
<a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
<?php
    foreach ($sistemi as $s) {
?>
<a class="button<?= $s->id == $sistem->id ? ' active' : '' ?>" href="<?= App::url('/pures/TSS/prezracevanje/' . $projectId . '/' . $s->id) ?>"><?= $s->id ?></a>
<?php
    }
?>
</p>
<h1>Analiza prezračevalnega sistema "<?= h($sistem->id) ?>"</h1>
<table border="1">
    <tr>
        <td colspan="2">Toplotna Cona:</td>
        <td colspan="2"><?= $sistem->idCone ?></td>
    </tr>
    <tr>
        <td colspan="2">Vrsta prezračevalne naprave:</td>
        <td colspan="2"><?= $sistem->vrsta ?></td>
    </tr>
    <tr>
        <td colspan="2">Rekuperator toplote razreda H2 ali H1:</td>
        <td colspan="2"><?= (isset($sistem->razredH1H2) && $sistem->razredH1H2) ? 'da' : 'ne' ?></td>
    </tr>
    <tr>
        <td colspan="2">Faktor krmiljenja:</td>
        <td colspan="2"><?= $sistem->faktorKrmiljenja ?></td>
    </tr>

    <?php
        if (isset($sistem->dovod)) {
    ?>
    <tr>
        <td rowspan="4">Dovod:</td>
    </tr>
    <tr>
        <td>Pretok:</td>
        <td><?= $this->numFormat($sistem->dovod->volumen, 0) ?></td>
        <td>m³/h</td>
    </tr>
    <tr>
        <td>Moč ventilatorja:</td>
        <td><?= $this->numFormat($sistem->dovod->mocVentilatorja, 3) ?></td>
        <td>kW</td>
    </tr>
    <tr>
        <td>Filter:</td>
        <td><?= $sistem->dovod->filter ?></td>
    </tr>
    <?php
        }
    ?>
    <?php
        if (isset($sistem->odvod)) {
    ?>
    <tr>
        <td rowspan="4">Odvod:</td>
    </tr>
    <tr>
        <td>Pretok:</td>
        <td><?= $this->numFormat($sistem->odvod->volumen, 0) ?></td>
        <td>m³/h</td>
    </tr>
    <tr>
        <td>Moč ventilatorja:</td>
        <td><?= $this->numFormat($sistem->odvod->mocVentilatorja, 3) ?></td>
        <td>kW</td>
    </tr>
    <tr>
        <td>Filter:</td>
        <td><?= $sistem->odvod->filter ?></td>
    </tr>
    <?php
        }
    ?>
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
        <td>E<sub>V,el,del,m</sub>; E<sub>V,el,del,an</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $sistem->potrebnaEnergija)) ?>
        <th class="right"><?= $this->numFormat(array_sum($sistem->potrebnaEnergija), 0) ?></th>
    </tr>
</table>