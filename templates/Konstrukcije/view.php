<?php
    use \App\Lib\Calc;
?>
<h1>Analiza netransparentne konstrukcije</h1>

<table>
    <tr>
        <td>Naziv:</td>
        <td><?= h($kons->naziv) ?></td>
        <td colspan="4"></td>
    </tr>
    <tr>
        <td>Tip:</td>
        <td colspan="4"><?= h($kons->TSG->naziv) ?></td>
    </tr>
    <tr>
        <td>U=</td>
        <td><?= number_format($kons->U, 3) ?> W/m2K</td>
        <td>U<sub>max</sub>=</td>
        <td><?= number_format($kons->TSG->Umax, 3) ?> W/m2K</td>
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
            <th></th>
            <th class="center">d<br />[m]</th>
            <th class="center w-10">&lambda;<br />[W/mK]</th>
            <th class="center w-10">&rho;<br />[kg/m<sup>3</sup>]</th>
            <th class="center w-10">c<sub>p</sub><br />[J/kg K]</th>
            <th class="center w-10">&mu;<br />[-]</th>
            <th class="center w-10">R<br />[m<sup>2</sup>K/W]</th>
            <th class="center w-10">s<sub>d</sub><br />[m]</th>
        </tr>
    </thead>
<?php
    foreach ($kons->materiali as $material) {
?>
    <tr>
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
<div style="height: 20rem;">
  <canvas id="myChart"></canvas>
</div>

<script src="<?= $this->url("/js/chartjs.min.js") ?>"></script>

<script>
    const ctx = document.getElementById('myChart');

    var temp = [
<?php
    $debelinaAccum = 0;
    $mesec = 0;

    printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, -0.05, $okolje->notranjaT[$mesec]);
    printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, 0, $kons->Tsi[$mesec]);

    foreach ($kons->materiali as $i => $material) {
        foreach ($material->racunskiSloji as $k => $sloj) {
            printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, $debelinaAccum + $sloj->debelina, $sloj->T[$mesec]);
            $debelinaAccum += $sloj->debelina;
        }
    }

    printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, $debelinaAccum, $kons->Tse[$mesec]);
    printf('{"x": %1$f, "y":%2$f}' . PHP_EOL, $debelinaAccum+0.05, $okolje->zunanjaT[$mesec]);
?>
    ];

    new Chart(ctx, {
        "type": 'scatter',
        "data": {
            "datasets": [
                { 
                "label":"Temperatura v konstrukciji",
                "data": temp,
                "fill":false,
                "borderColor": "#fa4444",
                "lineTension":0.1,
                showLine: true
                }
            ]
        },
        plugins: [{
    beforeDraw: chart => {
      var ctx = chart.ctx;
      var xAxis = chart.scales.x;
      var yAxis = chart.scales.y;

      ctx.fillStyle = "lightgray";
      ctx.rect(xAxis.getPixelForValue(temp[1].x), yAxis.top, xAxis.getPixelForValue(temp[temp.length - 2].x) - xAxis.getPixelForValue(temp[1].x), yAxis.bottom-yAxis.top);
      ctx.fill();

      temp.forEach((value, index) => {
        if (index > 0 && index < temp.length - 1) {
            var x = xAxis.getPixelForValue(temp[index].x);
            var yTop = yAxis.getPixelForValue(temp[index].y);

            ctx.save();
            ctx.strokeStyle = '#404040';
            ctx.beginPath();
            ctx.moveTo(x, yAxis.bottom);
            ctx.lineTo(x, yAxis.top);
            ctx.stroke();
            ctx.restore();
        }
      });
    }
  }],
        "options": {
            "scales": {
                x: {
                    type: "linear",
                    position: "bottom",
                    min: -0.10,
                    max: <?= $debelinaAccum ?> + 0.1
                }
            }
        }
    });
</script>


<h3>Prikaz tlaka in kondenzacije</h3>
<div style="height: 20rem;">
  <canvas id="myChart2"></canvas>
</div>
<div>
    <table border="1">
        <thead>
            <tr>
                <th></th>
                <th class="right">d<br />[cm]</th>
                <th class="right w-10">&lambda;<br />[W/mK]</th>
                <th class="right w-10">R [m<sup>2</sup>K/W]</th>
                <th class="right w-10">s<sub>d</sub><br />[m]</th>
                <th class="right w-10">T<br />[°C]</th>
                <th class="right w-10">p<sub>dej</sub><br />[Pa]</th>
                <th class="right w-10">p<sub>nas</sub><br />[Pa]</th>
                <th class="right w-10">g<sub>c</sub><br />[g/m<sup>2</sup> m]</th>
                <th class="right w-10">M<sub>a</sub><br />[g/m<sup>2</sup>]</th>
            </tr>
        </thead>
        <tr>
            <td>Prostor</td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"><?= $okolje->notranjaT[$mesec] ?></td>
            <td class="right"><?= round(Calc::nasicenTlak($okolje->notranjaT[$mesec]) * $okolje->notranjaVlaga[$mesec] / 100, 0) ?></td>
            <td class="right"><?= round(Calc::nasicenTlak($okolje->notranjaT[$mesec]), 0) ?></td>
        </tr>
        <tr>
            <td>Notr. površina</td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"><?= round($kons->Tsi[$mesec], 1) ?></td>
            <td class="right"><?= round($kons->dejanskiTlakSi[$mesec], 0) ?></td>
            <td class="right"><?= round($kons->nasicenTlakSi[$mesec], 0) ?></td>
        </tr>
<?php
    foreach ($kons->materiali as $material) {
        foreach ($material->racunskiSloji as $sloj) {
?>
        <tr>
            <td><?= $sloj->opis ?></td>
            <td class="right w-10"><?= round(($sloj->debelina ?? 0) * 100, 1) ?></td>
            <td class="right w-10"><?= round(($sloj->lambda ?? 0), 3) ?></td>
            <td class="right w-10"><?= !empty($sloj->lambda) ? round(($sloj->debelina ?? 0) / ($sloj->lambda ?? 0), 3) : '' ?></td>
            <td class="right w-10"><?= isset($sloj->Sd) ? round($sloj->Sd, 4) : round($sloj->debelina * $sloj->difuzijskaUpornost, 4) ?></td>
            <td class="right w-10"><?= round($sloj->T[$mesec], 1) ?></td>
            <td class="right w-10"><?= round($sloj->dejanskiTlak[$mesec], 1) ?></td>
            <td class="right w-10"><?= round($sloj->nasicenTlak[$mesec], 1) ?></td>
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
            <td class="right"><?= round($kons->dejanskiTlakSe[$mesec], 0) ?></td>
            <td class="right"><?= round($kons->nasicenTlakSe[$mesec], 0) ?></td>
        </tr>
        <tr>
            <td>Okolica</td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"><?= $okolje->zunanjaT[$mesec] ?></td>
            <td class="right"><?= round(Calc::nasicenTlak($okolje->zunanjaT[$mesec]) * $okolje->zunanjaVlaga[$mesec] / 100, 0) ?></td>
            <td class="right"><?= round(Calc::nasicenTlak($okolje->zunanjaT[$mesec]), 0) ?></td>
        </tr>
    </table>
</div>
<!--
<div>
    <h2>Kondenzacija</h2>
    <table border="1">
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
-->

<script src="<?= $this->url("/js/hammer.min.js") ?>"></script>
<script src="<?= $this->url("/js/chartjs-plugin-zoom.min.js") ?>"></script>

<script>
    const ctx2 = document.getElementById('myChart2');
    ////////////////////////////////////////////////////////
    var nasicenTlak = [
<?php
    printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, -($kons->Sd * 0.05), Calc::nasicenTlak($okolje->notranjaT[$mesec]));
    printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, 0, $kons->nasicenTlakSi[$mesec]);

    $totalSd = 0;
    foreach ($kons->materiali as $i => $material) {
        foreach ($material->racunskiSloji as $k => $sloj) {
            printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, $sloj->Sdn, $sloj->nasicenTlak[$mesec]);
            $totalSd += $sloj->Sd;
        }
    }

    printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, $kons->Sd, $kons->nasicenTlakSe[$mesec]);
    printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, $kons->Sd, Calc::nasicenTlak($okolje->zunanjaT[$mesec]));
?>
    ];

    ////////////////////////////////////////////////////////
    var dejanskiTlak = [
        {"x": 0, "y": <?= Calc::nasicenTlak($okolje->notranjaT[$mesec]) * $okolje->notranjaVlaga[$mesec] / 100 ?>},
        {"x": <?= $kons->Sd; ?>, "y": <?= Calc::nasicenTlak($okolje->zunanjaT[$mesec]) * $okolje->zunanjaVlaga[$mesec] / 100 ?>}
    ];

    ////////////////////////////////////////////////////////
    var dejanskiTlakTocke = [
<?php
    printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, 0, $kons->dejanskiTlakSi[$mesec]);

    foreach ($kons->materiali as $i => $material) {
        foreach ($material->racunskiSloji as $k => $sloj) {
            printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, $sloj->Sdn, $sloj->dejanskiTlak[$mesec]);
        }
    }

    printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, $kons->Sd, $kons->dejanskiTlakSe[$mesec]);
?>
    ];

    new Chart(ctx2, {
        "type": 'scatter',
        "data": {
            "datasets": [
                { 
                "label":"Nasičen Tlak",
                "data": nasicenTlak,
                "fill":false,
                "borderColor":"rgb(75, 192, 192)",
                showLine: true
                },
                {
                    "label":"Dejanski Tlak",
                    "data": dejanskiTlak,
                    "showLine": true
                },
                {
                    "label":"Dejanski Tlak Tocke",
                    "data": dejanskiTlakTocke,
                    "borderColor":"rgb(75, 50, 20)",
                    "showLine": true
                }
            ]
        },
        "plugins": [{
            beforeDraw: chart => {
            var ctx = chart.ctx;
            var xAxis = chart.scales.x;
            var yAxis = chart.scales.y;

            ctx.fillStyle = "lightgray";
            ctx.rect(xAxis.getPixelForValue(nasicenTlak[1].x), yAxis.top, xAxis.getPixelForValue(nasicenTlak[nasicenTlak.length - 2].x) - xAxis.getPixelForValue(nasicenTlak[1].x), yAxis.bottom-yAxis.top);
            ctx.fill();

            nasicenTlak.forEach((value, index) => {
                if (index > 0 && index < nasicenTlak.length - 1) {
                    var x = xAxis.getPixelForValue(nasicenTlak[index].x);
                    var yTop = yAxis.getPixelForValue(nasicenTlak[index].y);

                    ctx.save();
                    ctx.strokeStyle = '#404040';
                    ctx.beginPath();
                    ctx.moveTo(x, yAxis.bottom);
                    ctx.lineTo(x, yAxis.top);
                    ctx.stroke();
                    ctx.restore();
                }
            });
            }
        }],
        "options": {
            "scales": {
                x: {
                    type: "linear",
                    position: "bottom",
                    min: -0.10,
                    max: <?= $kons->Sd ?> + 0.1
                }
            },
            "plugins": {
                zoom: {
                    zoom: {
                        wheel: {
                            enabled: true,
                        },
                        pinch: {
                            enabled: true
                        },
                        mode: 'x',
                    },
                    pan: {
                        enabled: true,
                        mode: 'x',
                    },
                }
            }
        }
    });
</script>
