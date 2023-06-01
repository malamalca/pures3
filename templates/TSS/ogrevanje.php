<?php
    use App\Lib\Calc;
?>
<h1>Analiza TSS Ogrevanja "<?= h($sistem->id) ?>"</h1>


<table border="1">
<tr>
        <td colspan="1">Toplotna Cona:</td>
        <td colspan="3"><?= $sistem->idCone ?></td>
    </tr>
    <tr>
        <td>Vrsta:</td>
        <td></td>
        <td><?= $sistem->vrsta ?></td>
        <td></td>
    </tr>
    <tr>
        <td>Energent:</td>
        <td></td>
        <td><?= $sistem->energent ?></td>
        <td></td>
    </tr>
</table>

<?php
    if (!empty($sistem->prenosniki)) {
?>
<h2>Analiza končnih prenosnikov</h2>
<table border="1">
    <thead>
        <tr>
            <td></td>
            <td></td>
            <?= implode(PHP_EOL, array_map(fn($mes) => '<td class="center w-6">' . $mes . '</td>', Calc::MESECI)) ?>
            <td class="center">kWh/an</td>
        </tr>
    </thead>

    <?php
        foreach ($sistem->prenosniki as $prenosnik) {
    ?>
    <tr>
        <td rowspan="3"><?= h($prenosnik->id) ?></td>
        <td>Q<sub>H,em,ls</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $prenosnik->toplotneIzgube)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($prenosnik->toplotneIzgube), 0) ?></th>
    </tr>
    <tr>
        <td>W<sub>WH,em,aux</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 2) . '</td>', $prenosnik->potrebnaElektricnaEnergija)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($prenosnik->potrebnaElektricnaEnergija), 0) ?></th>
    </tr>
    <tr>
        <td>Q<sub>H,em,aux,rhh</td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $prenosnik->vracljiveIzgubeAux)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($prenosnik->vracljiveIzgubeAux), 0) ?></th>
    </tr>
    <?php
        }
    ?>
</table>
<?php
    }
?>

<h2>Analiza razvoda</h2>
<table border="1">
    <thead>
        <tr>
            <td></td>
            <td></td>
            <?= implode(PHP_EOL, array_map(fn($mes) => '<td class="center w-6">' . $mes . '</td>', Calc::MESECI)) ?>
            <td class="center">kWh/an</td>
        </tr>
    </thead>

    <?php
            foreach ($sistem->razvodi as $razvod) {
    ?>
    <tr>
        <td rowspan="4"><?= h($razvod->id ?? '') ?></td>
        <td>Q<sub>H,dis,ls</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $razvod->toplotneIzgube)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($razvod->toplotneIzgube), 0) ?></th>
    </tr>
    <tr>
        <td>Q<sub>H,dis,rhh</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $razvod->vracljiveIzgube)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($razvod->vracljiveIzgube), 0) ?></th>
    </tr>
    <tr>
        <td>W<sub>WH,dis,aux</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $razvod->potrebnaElektricnaEnergija)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($razvod->potrebnaElektricnaEnergija), 0) ?></th>
    </tr>
    <tr>
        <td>Q<sub>H,dis,aux,rhh</td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $razvod->vracljiveIzgubeAux)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($razvod->vracljiveIzgubeAux), 0) ?></th>
    </tr>
    <?php
            }
    ?>
</table>

<?php
    if (!empty($sistem->hranilniki)) {
?>
<h2>Analiza hranilnikov</h2>
<table border="1">
    <thead>
        <tr>
            <td></td>
            <td></td>
            <?= implode(PHP_EOL, array_map(fn($mes) => '<td class="center w-6">' . $mes . '</td>', Calc::MESECI)) ?>
            <td class="center">kWh/an</td>
        </tr>
    </thead>

    <?php
            foreach ($sistem->hranilniki as $hranilnik) {
    ?>
    <tr>
        <td rowspan="4"><?= h($hranilnik->id ?? '') ?></td>
        <td>Q<sub>W,dis,ls</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $hranilnik->toplotneIzgube)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($hranilnik->toplotneIzgube), 0) ?></th>
    </tr>
    <?php
            }
    ?>
</table>
<?php
    }
?>

<h2>Analiza generatorja</h2>
<table border="1">
    <thead>
        <tr>
            <td></td>
            <td></td>
            <?= implode(PHP_EOL, array_map(fn($mes) => '<td class="center w-6">' . $mes . '</td>', Calc::MESECI)) ?>
            <td class="center">kWh/an</td>
        </tr>
    </thead>

    <?php
            foreach ($sistem->generatorji as $generator) {
    ?>
    <tr>
        <td rowspan="2"><?= h($generator->id ?? '') ?></td>
        <td>E<sub>TČ</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $generator->potrebnaEnergija)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($generator->potrebnaEnergija), 0) ?></th>
    </tr>
    <tr>
        <td>W<sub>TČ,aux</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $generator->potrebnaElektricnaEnergija)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($generator->potrebnaElektricnaEnergija), 0) ?></th>
    </tr>
    <?php
            }
    ?>
</table>

<h2>Analiza sistema</h2>
<table border="1">
    <thead>
        <tr>
            <td></td>
            <td></td>
            <?= implode(PHP_EOL, array_map(fn($mes) => '<td class="center w-6">' . $mes . '</td>', Calc::MESECI)) ?>
            <td class="center">kWh/an</td>
        </tr>
    </thead>

    <tr>
        <td rowspan="4"><?= h($sistem->id ?? '') ?></td>
        <td>E<sub>H,del,aux</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $sistem->potrebnaElektricnaEnergija)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($sistem->potrebnaElektricnaEnergija), 0) ?></th>
    </tr>
    <tr>
        <td>Q<sub>H,del</td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $sistem->potrebnaEnergija)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($sistem->potrebnaEnergija), 0) ?></th>
    </tr>

    <tr>
        <td>Q<sub>H,environment,del</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $sistem->obnovljivaEnergija)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($sistem->obnovljivaEnergija), 0) ?></th>
    </tr>

    <tr>
        <td>∑ Q<sub>H,rhh</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $sistem->vracljiveIzgube)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($sistem->vracljiveIzgube), 0) ?></th>
    </tr>
</table>