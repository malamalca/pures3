<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<p class="actions">
<a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
<?php
    foreach ($sistemi as $s) {
?>
<a class="button<?= $s->id == $sistem->id ? ' active' : '' ?>" href="<?= App::url('/pures/TSS/oht/' . $projectId . '/' . $s->id) ?>"><?= $s->id ?></a>
<?php
    }
?>
</p>
<h1>Analiza TSS OHT "<?= h($sistem->id) ?>"</h1>
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
<?php
        foreach ($sistem->prenosniki as $prenosnik) {
?>
<table border="1">
    <tr>
        <th colspan="2">Prenosnik:</th>
        <th colspan="3"><?= h($prenosnik->id) ?></th>
    </tr>
    <tr>
        <td colspan="2">Vrsta ogreval:</td>
        <td colspan="3"><?= h($prenosnik->vrsta) ?></td>
    </tr>
    <tr>
        <td colspan="2">Hidravlično uravnoteženje razvoda:</td>
        <td colspan="3"><?= h($prenosnik->hidravlicnoUravnotezenje ?? '') ?></td>
    </tr>
    <tr>
        <td colspan="2">Regulacija temperature prostora:</td>
        <td colspan="3"><?= h($prenosnik->regulacijaTemperature) ?></td>
    </tr>
</table>
<br />
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
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 2) . '</td>', $prenosnik->vracljiveIzgubeAux)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($prenosnik->vracljiveIzgubeAux), 0) ?></th>
    </tr>
</table>
<?php
        }
    }
?>

<?php
    if (!empty($sistem->razvodi)) {
?>
<h2>Analiza razvoda</h2>
<?php
        foreach ($sistem->razvodi as $razvod) {
?>
    <table border="1">
        <tr>
            <th colspan="2">Razvod:</th>
            <th colspan="3"><?= $razvod->id ?></th>
        </tr>
        <tr>
            <td colspan="2">Sistem:</td>
            <td colspan="3"><?= $razvod->sistem ?? '' ?></td>
        </tr>
        <tr>
            <td colspan="2">Črpalka:</td>
            <td colspan="3"><?= empty($razvod->crpalka) ? 'NE' : ($this->numFormat($razvod->crpalka->moc ?? 0, 1) . ' W') ?></td>
        </tr>
        <tr>
            <td colspan="2">Vodi:</td>
            <td>Dolzina L [m]</td>
            <td>Izolacija U [W/mk]</td>
            <td>Delež v ogrevani coni [%]</td>
        </tr>
        <?php
                foreach ($razvod->vodi as $vod) {
        ?>
        <tr>
            <td colspan="2"><?= $vod->vrsta ?></td>
            <td class="center"><?= $this->numFormat($vod->dolzina ?? 0, 1) ?></td>
            <td class="center"><?= $this->numFormat($vod->toplotnaPrevodnost ?? 0, 1) ?></td>
            <td class="center"><?= $this->numFormat($vod->delezVOgrevaniConi ?? 0, 1) ?></td>
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
            <td></td>
            <?= implode(PHP_EOL, array_map(fn($mes) => '<td class="center w-6">' . $mes . '</td>', Calc::MESECI)) ?>
            <td class="center">kWh/an</td>
        </tr>
    </thead>
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
</table>
<br />
<?php
        }
    }
?>

<?php
    if (!empty($sistem->hranilniki)) {
?>
<h2>Analiza hranilnikov</h2>
<?php
        foreach ($sistem->hranilniki as $hranilnik) {
?>
<table border="1">
    <tr>
        <th colspan="2">Hranilnik:</th>
        <th colspan="2"><?= $hranilnik->id ?></th>
    </tr>
    <tr>
        <td>Volumen hranilnika:</td>
        <td>V<sub>sto</sub></td>
        <td><?= $this->numFormat($hranilnik->volumen, 1) ?></td>
        <td>L</td>
    </tr>
</table>
<br />
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
        <td rowspan="4"><?= h($hranilnik->id ?? '') ?></td>
        <td>Q<sub>W,sto,ls</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $hranilnik->toplotneIzgube)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($hranilnik->toplotneIzgube), 0) ?></th>
    </tr>
</table>
<?php
        }
    }
?>

<?php
    if (!empty($sistem->generatorji)) {
?>
<h2>Analiza generatorja</h2>
<?php
    foreach ($sistem->generatorji as $generator) {
        $podsistemi = [];
        if (isset($generator->toplotneIzgube->tsv)) {
            $podsistemi[] = 'tsv';
        }
        if (isset($generator->toplotneIzgube->ogrevanje)) {
            $podsistemi[] = 'ogrevanje';
        }
        if (isset($generator->toplotneIzgube->hlajenje)) {
            $podsistemi[] = 'hlajenje';
        }
?>
<table border="1">
    <tr>
        <th colspan="2">Generator:</th>
        <th colspan="2"><?= $generator->id ?></th>
    </tr>
    <tr>
        <td>Nazivna moč:</td>
        <td>P<sub>n,gen</sub></td>
        <td><?= $this->numFormat($generator->nazivnaMoc, 1) ?></td>
        <td>kW</td>
    </tr>
    <?php
        foreach ($generator->porociloPodatki as $podatek) {
            echo $this->element('elements'. DS . 'porociloPodatek', ['podatek' => $podatek]);
        }
    ?>
</table>
<br />
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
        foreach ($podsistemi as $podsistem) {
            switch ($podsistem) {
                case 'hlajenje':
                    $subscript = 'C';
                    break;
                case 'tsv':
                    $subscript = 'W';
                    break;
                default:
                    $subscript = 'H';
            }
    ?>
    <tr>
        <td rowspan="<?= 2 + (isset($generator->porociloNizi) ? count($generator->porociloNizi) : 0) ?>"><?= h($generator->id ?? '') ?> <?= $podsistem ?></td>
        <td>Q<sub><?= $subscript ?>,del,m</sub>; Q<sub><?= $subscript ?>,del,an</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $generator->vneseneIzgube->$podsistem)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($generator->vneseneIzgube->$podsistem), 0) ?></th>
    </tr>
    <tr>
        <td>W<sub><?= $subscript ?>,gen,aux</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $generator->potrebnaElektricnaEnergija->$podsistem)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($generator->potrebnaElektricnaEnergija->$podsistem), 0) ?></th>
    </tr>
    <?php
        }
    ?>

    <?php
        foreach ($generator->porociloNizi as $niz) {
            echo $this->element('elements'. DS . 'porociloNiz', ['niz' => $niz]);
        }
    ?>
</table>
<?php
        }
    }
?>

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
        <td>E<sub>H/W/C,del,aux</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $sistem->potrebnaElektricnaEnergija)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($sistem->potrebnaElektricnaEnergija), 0) ?></th>
    </tr>
    <tr>
        <td>Q<sub>H/W/C,del</td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $sistem->potrebnaEnergija)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($sistem->potrebnaEnergija), 0) ?></th>
    </tr>

    <tr>
        <td>Q<sub>H/W/C,environment,del</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $sistem->obnovljivaEnergija)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($sistem->obnovljivaEnergija), 0) ?></th>
    </tr>

    <!--<tr>
        <td>∑ Q<sub>H,rhh</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center w-6">' . $this->numFormat($mesecnaVrednost, 1) . '</td>', $sistem->vracljiveIzgube)) ?>
        <th class="right w-6"><?= $this->numFormat(array_sum($sistem->vracljiveIzgube), 0) ?></th>
    </tr>-->
</table>