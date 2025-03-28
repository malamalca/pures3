<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<h1>Podatki o projektu "<?= h($splosniPodatki->stavba->naziv) ?>"</h1>

<p class="actions">
    <a class="button" href="<?= App::url('/pures/projekti/naslovnica/' . $projectId) ?>">Naslovnica</a>
<?php
    $sourceFolder = App::getProjectFolder('Hrup', $projectId, 'podatki');
    $sourceFilename = $sourceFolder . 'tehnicnoPorocilo.md';

    $porocilo = '';
    if (file_exists($sourceFilename)) {
?>
    <a class="button" href="<?= App::url('/pures/projekti/porocilo/' . $projectId) ?>">Tehn. poročilo</a>
<?php
    }
?>
    <a class="button" href="<?= App::url('/pures/konstrukcije/index/' . $projectId) ?>">Konstrukcije</a>
    <a class="button" href="<?= App::url('/pures/projekti/analiza/' . $projectId) ?>">Analiza GF</a>
    <a class="button" href="<?= App::url('/pures/projekti/snes/' . $projectId) ?>">Analiza SNES</a>
    <a class="button" href="<?= App::url('/pures/izkazi/splosniPodatki/' . $projectId) ?>">Izkaz</a>
    </p>
<p class="actions">
<?php
    if (!empty($cone)) {
        foreach ($cone as $cona) {
?>
<a class="button" href="<?= App::url('/pures/cone/analiza/' . $projectId . '/' . $cona->id) ?>"><?= h($cona->id) ?></a>
<?php
        }
    }
?>
</p>
<p class="actions">
<?php
    $vrsteTSS = ['OHT' => $sistemiOHT, 'prezracevanje' => $sistemiPrezracevanja,
        'razsvetljava' => $sistemiRazsvetljave,
        'fotovoltaika' => $sistemiSTPE];

    foreach ($vrsteTSS as $vrstaTSS => $sistemi) {
        if ($sistemi) {
?>
    <a class="button" href="<?= App::url('/pures/TSS/' . strtolower($vrstaTSS) . '/' . $projectId . '/' . $sistemi[0]->id) ?>">TSS <?= $vrstaTSS ?></a>
<?php
        }
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

    <tr>
        <td colspan="2">Klasifikacija</td>
        <td colspan="2" class="left"><?= h($splosniPodatki->stavba->klasifikacija) ?></td>
    </tr>
    <tr>
        <td colspan="2">Opredelitev stavbe</td>
        <td colspan="2" class="left"><?= h($splosniPodatki->stavba->vrsta) ?></td>
    </tr>
    <tr>
        <td colspan="2">Vrsta gradnje</td>
        <td colspan="2" class="left"><?= h($splosniPodatki->stavba->tip) ?></td>
    </tr>
    <tr>
        <td colspan="2">Javna stavba</td>
        <td colspan="2" class="left"><?= h($splosniPodatki->stavba->javna ? "DA" : "NE") ?></td>
    </tr>
    <tr><td colspan="4"></tr>

    <tr>
        <td colspan="2">Povprečna letna T (°C)</td>
        <td colspan="2" class="left"><?= $this->numFormat($okolje->povprecnaLetnaTemp, 1) ?></td>
    </tr>
    <tr>
        <td colspan="2">Projektna zimska T (°C)</td>
        <td colspan="2" class="left"><?= $this->numFormat($okolje->projektnaZunanjaT, 1) ?></td>
    </tr>
    <tr>
        <td colspan="2">Energija sončnega obsevanja (kWh/m²)</td>
        <td colspan="2" class="left"><?= $this->numFormat($okolje->energijaSoncnegaObsevanja, 1) ?></td>
    </tr>
</table>
<br />
<table border="1">
    <thead>
        <tr>
            <td></td>
            <td></td>
            <?= implode(PHP_EOL, array_map(fn($mes) => '<td class="center">' . $mes . '</td>', Calc::MESECI)) ?>
        </tr>
    </thead>
    <tr>
        <td>Temperatura (°C)</td>
        <td>θ<sub>e,m</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 0) . '</td>', $okolje->zunanjaT)) ?>
    </tr>
    <tr>
        <td>Rel. vlažnost (%)</td>
        <td>&#934;<sub>e,m</sub></td>
        <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 0) . '</td>', $okolje->zunanjaVlaga)) ?>
    </tr>
</table>
