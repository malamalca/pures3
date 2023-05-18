<?php
//var_dump($okolje);
//die;
?>
<div>
  <canvas id="myChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctx = document.getElementById('myChart');

    var data = [
<?php
    $debelinaAccum = 0;

    printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, -0.05, $okolje->notranjaT['jan']);
    printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, 0, $kons->Tsi['jan']);

    foreach ($kons->materiali as $i => $material) {
        foreach ($material->racunskiSloji as $k => $sloj) {
            printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, $debelinaAccum + $sloj->debelina, $sloj->T['jan']);
            $debelinaAccum += $sloj->debelina;
        }
    }

    printf('{"x": %1$f, "y":%2$f},' . PHP_EOL, $debelinaAccum, $kons->Tse['jan']);
    printf('{"x": %1$f, "y":%2$f}' . PHP_EOL, $debelinaAccum+0.05, $okolje->zunanjaT['jan']);
?>
    ];

    new Chart(ctx, {
        "type": 'scatter',
        "data": {
            "datasets": [
                { 
                "label":"Temperatura v konstrukciji",
                "data": data,
                "fill":false,
                "borderColor":"rgb(75, 192, 192)",
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

      console.log(ctx);
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
                    max: <?= $debelinaAccum ?> + 0.1
                }
            }
        }
    });
</script>
 