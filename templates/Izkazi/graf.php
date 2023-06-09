<?php
use App\Lib\Calc;
//var_dump($okolje->notranjaVlaga);
//die;

$mesec = $_GET['mesec'] ?? 1;
if (!in_array($mesec, array_keys(Calc::MESECI))) {
    $mesec = 1;
}
$mesec--;

?>

<h1><?= $kons->naziv ?></h1>

<div>
  <canvas id="myChart"></canvas>
</div>
<div>
    U: <?= $kons->U ?><br />
    maxGm: <?= $kons->maxGm ?><br />
    <table>
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
            <td class="right"><?= round($kons->Tsi[$mesec], 3) ?></td>
            <td class="right"><?= round($kons->dejanskiTlakSi[$mesec], 0) ?></td>
            <td class="right"><?= round($kons->nasicenTlakSi[$mesec], 0) ?></td>
        </tr>
<?php
    foreach ($kons->materiali as $material) {
        foreach ($material->racunskiSloji as $sloj) {
?>
        <tr>
            <td><?= $sloj->opis ?></td>
            <td class="right w-10"><?= round(($sloj->debelina ?? 0) * 100, 3) ?></td>
            <td class="right w-10"><?= round(($sloj->lambda ?? 0), 3) ?></td>
            <td class="right w-10"><?= !empty($sloj->lambda) ? round(($sloj->debelina ?? 0) / ($sloj->lambda ?? 0), 3) : '' ?></td>
            <td class="right w-10"><?= isset($sloj->Sd) ? round($sloj->Sd, 4) : round($sloj->debelina * $sloj->difuzijskaUpornost, 4) ?></td>
            <td class="right w-10"><?= round($sloj->T[$mesec], 3) ?></td>
            <td class="right w-10"><?= round($sloj->dejanskiTlak[$mesec], 0) ?></td>
            <td class="right w-10"><?= round($sloj->nasicenTlak[$mesec], 0) ?></td>
            <td class="right w-10"><?= isset($sloj->gc[$mesec]) ? round($sloj->gc[$mesec] / 100, 5) : '' ?></td>
            <td class="right w-10"><?= isset($sloj->gm[$mesec]) ? round($sloj->gm[$mesec] / 100, 5) : '' ?></td>
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

<div>
    <h2>Kondenzacija</h2>
    <table border="1">
        <tr>
            <td>&nbsp;</td>
            <?php
            foreach (array_keys(Calc::MESECI) as $mes) {
    ?>

            <td colspan="2"><?= Calc::MESECI[$mes] ?></td>
    <?php
            }
    ?>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <?php
            foreach (array_keys(Calc::MESECI) as $mes) {
    ?>
            <td>G<sub>c</sub></td>
            <td>M<sub>a</sub></td>
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
            <td class="right w-10" style="background-color:#f0f0f0"><?= isset($sloj->gc[$mes]) ? round($sloj->gc[$mes]/1000, 4) : '' ?></td>
            <td class="right w-10" style="border-right: solid 2px black;"><?= isset($sloj->gm[$mes]) ? round($sloj->gm[$mes]/1000, 4) : '' ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8"></script>
<script src="<?= $this->url("/js/chartjs-plugin-zoom.min.js") ?>"></script>

<script>
    const ctx = document.getElementById('myChart');

    ////////////////////////////////////////////////////////
    var data = [
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

    

    new Chart(ctx, {
        "type": 'scatter',
        "data": {
            "datasets": [
                { 
                "label":"Nasičen Tlak",
                "data": data,
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
        plugins: [{
    beforeDraw: chart => {
      var ctx = chart.ctx;
      var xAxis = chart.scales.x;
      var yAxis = chart.scales.y;

      ctx.fillStyle = "lightgray";
      ctx.rect(xAxis.getPixelForValue(data[1].x), yAxis.top, xAxis.getPixelForValue(data[data.length - 2].x) - xAxis.getPixelForValue(data[1].x), yAxis.bottom-yAxis.top);
      ctx.fill();

      data.forEach((value, index) => {
        if (index > 0 && index < data.length - 1) {
            var x = xAxis.getPixelForValue(data[index].x);
            var yTop = yAxis.getPixelForValue(data[index].y);

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
 