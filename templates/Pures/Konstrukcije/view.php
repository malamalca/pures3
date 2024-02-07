<?php
    use \App\Core\App;
    use \App\Lib\Calc;
    use \App\Lib\CalcKonstrukcije;
    use \App\Lib\Charts\PuresChart;
?>
<h1>Analiza netransparentne konstrukcije</h1>

<table>
    <tr>
        <td>Naziv:</td>
        <td><b><?= h($kons->id) ?> :: <?= h($kons->naziv) ?></b></td>
        <td colspan="4"></td>
    </tr>
    <tr>
        <td>Tip:</td>
        <td colspan="4"><?= h($kons->TSG->naziv) ?></td>
    </tr>
    <tr>
        <td>U=</td>
        <td><?= number_format($kons->U, 3) ?> W/m²K</td>
        <td>U<sub>max</sub>=</td>
        <td><?= number_format($kons->TSG->Umax, 3) ?> W/m²K</td>
        <td><?= $kons->TSG->Umax > $kons->U ? 'Ustreza' : 'Ne ustreza' ?></td>
    </tr>
    
    <tr>
        <td>f<sub>Rsi</sub>=</td>
        <td><?= number_format($kons->fRsi[0], 3) ?></td>
        <td>f<sub>Rsi,min</sub>=</td>
        <td><?= number_format(max($okolje->minfRsi), 3) ?></td>
        <td><?= max($okolje->minfRsi) < $kons->fRsi[0] ? 'Ustreza' : 'Ne ustreza' ?></td>
    </tr>
</table>
<br /><br />
<table border="1" width="100%">
    <thead>
        <tr>
            <th class="center">Št.</th>
            <th>Naziv</th>
            <th class="center">d<br />[m]</th>
            <th class="center w-10">&lambda;<br />[W/mK]</th>
            <th class="center w-10">&rho;<br />[kg/m³]</th>
            <th class="center w-10">c<sub>p</sub><br />[J/kg K]</th>
            <th class="center w-10">&mu;<br />[-]</th>
            <th class="center w-10">R<br />[m²K/W]</th>
            <th class="center w-10">s<sub>d</sub><br />[m]</th>
        </tr>
    </thead>
<?php
    foreach ($kons->materiali as $k => $material) {
?>
    <tr>
        <td class="center"><?= $k+1 ?></td>
        <td class="left"><?= h($material->opis) ?></td>
        <td class="center"><?= number_format($material->debelina, 3, ',', '') ?></td>
        <td class="center"><?= number_format($material->lambda, 3, ',', '') ?></td>
        <td class="center"><?= number_format($material->gostota, 0, ',', '') ?></td>
        <td class="center"><?= number_format($material->specificnaToplota, 0, ',', '') ?></td>
        <td class="center"><?= number_format($material->difuzijskaUpornost, 1, ',', '') ?></td>
        <td class="center"><?= number_format($material->R, 3, ',', '') ?></td>
        <td class="center"><?= number_format($material->Sd, 3, ',', '') ?></td>
    </tr>
<?php
    }
?>
</table>

<h3>Prikaz temperature v konstrukciji</h3>
<?php
    $mesec = 0;

    $temperatures = [];
    $categories = [];
    $layers = [];

    $temperatures[] = $okolje->notranjaT[$mesec];
    $temperatures[] = $kons->Tsi[$mesec];

    $categories[] = -$kons->debelina * 0.02;
    $categories[] = 0;

    foreach ($kons->materiali as $i => $material) {
        $temperatures[] = $material->T[$mesec];
        $categories[] = $categories[count($categories)-1] + $material->debelina;

        $color = $material->lambda < 0.05 ? 1 : ($material->lambda < 0.2 ? 2 : ($material->lambda < 0.7 ? 3 : 4));
        $layers[] = ['thickness' => $material->debelina, 'title' => $material->opis, 'color' => $color];
    }

    $categories[] = $kons->debelina + $kons->debelina * 0.02;
    $temperatures[] = $okolje->zunanjaT[$mesec];

    $png = (new PuresChart(['series' => [$temperatures], 'category' => $categories], [
        'layers' => $layers,
        'seriesColor' => ['ff0000'],
        'seriesThickness' => [4],
        'showCategoryLines' => false,
        'showCategoryValues' => false
    ]))->draw();
?>

<img src="data:image/png;base64,<?= base64_encode($png) ?>" style="width: 600px"/>

<h3>Prikaz tlaka in kondenzacije</h3>
<?php
    $mesec = 0;

    $layers = [];
    $nasicenTlak = [];
    $dejanskiTlak = [];
    $categories = [];

    $nasicenTlak[] = Calc::nasicenTlak($okolje->notranjaT[$mesec]);
    $nasicenTlak[] = $kons->nasicenTlakSi[$mesec];

    $dejanskiTlak[] = $kons->dejanskiTlakSi[$mesec];
    $dejanskiTlak[] = $kons->dejanskiTlakSi[$mesec];

    $categories[] = -$kons->Sd * 0.02;
    $categories[] = 0;

    foreach ($kons->materiali as $i => $material) {
        foreach ($material->racunskiSloji as $k => $sloj) {
            $nasicenTlak[] = $sloj->nasicenTlak[$mesec];
            $dejanskiTlak[] = $sloj->dejanskiTlak[$mesec];
            $thicknesses[] = $sloj->Sd;

            $categories[] = $categories[count($categories)-1] + $sloj->Sd;
        }
        $color = $material->lambda < 0.05 ? 1 : ($material->lambda < 0.2 ? 2 : ($material->lambda < 0.7 ? 3 : 4));
        $layers[] = ['thickness' => $material->Sd, 'title' => $material->opis, 'color' => $color];
    }

    $nasicenTlak[] = Calc::nasicenTlak($okolje->zunanjaT[$mesec]);
    $dejanskiTlak[] = $kons->dejanskiTlakSe[$mesec];
    $categories[] = $kons->Sd + $kons->Sd * 0.02;


    $png = (new PuresChart(['series' => [$nasicenTlak, $dejanskiTlak], 'category' => $categories], [
        'layers' => $layers,
        'seriesColor' => ['ff0000', '0000ff'],
        'seriesThickness' => [4],
        'showCategoryLines' => false,
        'showCategoryValues' => false
    ]))->draw();
?>

<img src="data:image/png;base64,<?= base64_encode($png) ?>" style="width: 600px"/>

<div>
    <table border="1">
        <thead>
            <tr>
                <th></th>
                <th class="right">d<br />[cm]</th>
                <th class="right w-10">&lambda;<br />[W/mK]</th>
                <th class="right w-10">R [m²3K/W]</th>
                <th class="right w-10">s<sub>d</sub><br />[m]</th>
                <th class="right w-10">T<br />[°C]</th>
                <th class="right w-10">p<sub>dej</sub><br />[Pa]</th>
                <th class="right w-10">p<sub>nas</sub><br />[Pa]</th>
                <th class="right w-10">g<sub>c</sub><br />[g/m² m]</th>
                <th class="right w-10">M<sub>a</sub><br />[g/m²]</th>
            </tr>
        </thead>
        <tr>
            <td>Prostor</td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"><?= $okolje->notranjaT[$mesec] ?></td>
            <td class="right"><?= round(Calc::nasicenTlak($okolje->notranjaT[$mesec]) * $okolje->notranjaVlaga[$mesec] / 100, 2) ?></td>
            <td class="right"><?= round(Calc::nasicenTlak($okolje->notranjaT[$mesec]), 2) ?></td>
        </tr>
        <tr>
            <td>Notr. površina</td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"><?= round($kons->Tsi[$mesec], 1) ?></td>
            <td class="right"><?= round($kons->dejanskiTlakSi[$mesec], 2) ?></td>
            <td class="right"><?= round($kons->nasicenTlakSi[$mesec], 2) ?></td>
        </tr>
<?php
    foreach ($kons->materiali as $material) {
        foreach ($material->racunskiSloji as $sloj) {
?>
        <tr>
            <td><?= $sloj->opis ?></td>
            <td class="right w-10"><?= $this->numFormat(round(($sloj->debelina ?? 0) * 100, 1), 2) ?></td>
            <td class="right w-10"><?= round(($sloj->lambda ?? 0), 3) ?></td>
            <td class="right w-10"><?= !empty($sloj->lambda) ? round(($sloj->debelina ?? 0) / ($sloj->lambda ?? 0), 3) : '' ?></td>
            <td class="right w-10"><?= isset($sloj->Sd) ? round($sloj->Sd, 4) : round($sloj->debelina * $sloj->difuzijskaUpornost, 4) ?></td>
            <td class="right w-10"><?= round($sloj->T[$mesec], 1) ?></td>
            <td class="right w-10"><?= round($sloj->dejanskiTlak[$mesec], 2) ?></td>
            <td class="right w-10"><?= round($sloj->nasicenTlak[$mesec], 2) ?></td>
            <td class="right w-10"><?= isset($sloj->gc->$mesec) ? round($sloj->gc->$mesec, 5) : '' ?></td>
            <td class="right w-10"><?= isset($sloj->gm->$mesec) ? round($sloj->gm->$mesec, 5) : '' ?></td>
        </tr>
<?php
        }
    }
?>
        <tr>
            <td>Zun. površina</td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"><?= round($kons->Tse[$mesec], 1) ?></td>
            <td class="right"><?= round($kons->dejanskiTlakSe[$mesec], 2) ?></td>
            <td class="right"><?= round($kons->nasicenTlakSe[$mesec], 2) ?></td>
        </tr>
        <tr>
            <td>Okolica</td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"><?= $okolje->zunanjaT[$mesec] ?></td>
            <td class="right"><?= round(Calc::nasicenTlak($okolje->zunanjaT[$mesec]) * $okolje->zunanjaVlaga[$mesec] / 100, 2) ?></td>
            <td class="right"><?= round(Calc::nasicenTlak($okolje->zunanjaT[$mesec]), 0) ?></td>
        </tr>
    </table>
</div>

<?php
    if (isset($kons->maxGm) && ($kons->maxGm == -1 || $kons->maxGm > 0)) {
?>
<div>
    <h2>Kondenzacija</h2>
    <table border="1" class="small">
        <tr>
            <td>&nbsp;</td>
            <?php
            foreach (array_keys(Calc::MESECI) as $mes) {
    ?>

            <td colspan="2" class="center w-6"><?= Calc::MESECI[$mes] ?></td>
    <?php
            }
    ?>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <?php
            foreach (array_keys(Calc::MESECI) as $mes) {
    ?>
            <td class="right w-4">M<sub>a</sub></td>
            <td class="right w-4">g<sub>c</sub></td>
    <?php
            }
    ?>
        </tr>

    <?php
    foreach ($kons->materiali as $material) {
        foreach ($material->racunskiSloji as $sloj) {
    ?>

        <tr>
            <td><?= $sloj->opis ?></td>
    <?php
            foreach (array_keys(Calc::MESECI) as $mes) {
    ?>

            <td class="right w-4" style="background-color:#f0f0f0;"><?= isset($sloj->gm->$mes) ? round($sloj->gm->$mes/1000, 4) : '&nbsp;' ?></td>
            <td class="right w-4" style="border-right: solid 2px black;"><?= isset($sloj->gc->$mes) ? round($sloj->gc->$mes/1000, 4) : '&nbsp;' ?></td>
            
    <?php
            }
    ?>
        </tr>
    <?php
        }
    }
    ?>
    </table>
</div>
<?php
    }
?>