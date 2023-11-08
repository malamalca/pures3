<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<h1>Analiza fotonapetostnega sistema "<?= h($sistem->id) ?>"</h1>

<p class="actions">
<a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>
<table border="1">
    <tr>
        <td colspan="2">Toplotna Cona:</td>
        <td colspan="2"><?= $sistem->idCone ?></td>
    </tr>
    <tr>
        <td>Površina PV modulov:</td>
        <td>A<sub>PV</sub></td>
        <td><?= $sistem->povrsina ?></td>
        <td>m2</td>
    </tr>
    <tr>
        <td colspan="2">Orientacija:</td>
        <td colspan="2"><?= $sistem->orientacija ?></td>
    </tr>
    <tr>
        <td colspan="2">Naklon:</td>
        <td><?= $sistem->naklon ?></td>
        <td>°</td>
    </tr>
    <tr>
        <td colspan="2">Vgradnja PV modulov:</td>
        <td colspan="2"><?= $sistem->vgradnja ?></td>
    </tr>
    <tr>
        <td colspan="2">Vrsta sončnih celic:</td>
        <td colspan="2"><?= $sistem->vrsta ?></td>
    </tr>
    <tr>
        <td>Koeficient vršne moči:</td>
        <td>K<sub>pk</sub></td>
        <td><?= $this->numFormat($sistem->koeficientMoci, 1) ?></td>
        <td>kW/m2</td>
    </tr>
    <tr>
        <td>Nazivna moč fotonapetostnega sistema:</td>
        <td>P<sub>pk</sub></td>
        <td><?= $this->numFormat($sistem->nazivnaMoc, 1) ?></td>
        <td>kW</td>
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
        <td><span title="Skupna potrebna električna energija za delovanje TSS stavbe">E<sub>B,us,tot,m</sub>; E<sub>B,us,tot,m,an</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $sistem->potrebnaEnergija)) ?>
        <th class="right"><?= $this->numFormat(array_sum($sistem->potrebnaEnergija), 0) ?></th>
    </tr>
    <tr>
        <td><span title="Celotna energija sončnega obsevanja">E<sub>PV,pr,m</sub>; E<sub>PV,pr,m,an</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $sistem->proizvedenaElektricnaEnergija)) ?>
        <th class="right"><?= $this->numFormat(array_sum($sistem->proizvedenaElektricnaEnergija), 0) ?></th>
    </tr>
    <tr>
        <td><span title="Celotna energija sončnega obsevanja">f<sub>match,m</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 2) . '</td>', $sistem->faktorUjemanja)) ?>
        <th class="right">-</th>
    </tr>
    <tr>
        <td><span title="Porabljena energija v objektu">E<sub>PV,used,m</sub>; E<sub>PV,used,m,an</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $sistem->porabljenaEnergija)) ?>
        <th class="right"><?= $this->numFormat(array_sum($sistem->porabljenaEnergija), 0) ?></th>
    </tr>
    <tr>
        <td><span title="Oddana energija v omrežje">E<sub>PV,exp,m</sub>; E<sub>PV,exp,m,an</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $sistem->oddanaElektricnaEnergija)) ?>
        <th class="right"><?= $this->numFormat(array_sum($sistem->oddanaElektricnaEnergija), 0) ?></th>
    </tr>
</table>