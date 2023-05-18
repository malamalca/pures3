<?php
declare(strict_types=1);

$inputZunanjaT = [-1, 1, 6, 10, 15, 18, 20, 19, 15, 10, 4, 1];
    $inputZunanjaVlaga = [82 , 77, 72, 71, 73, 72, 75, 76, 80, 82, 84, 85];

    $okolje = \App\Lib\CalcOkolje::notranjeOkolje(['zunanjaT' => $inputZunanjaT, 'zunanjaVlaga' => $inputZunanjaVlaga]);

    // compare with KI
if (!empty($compareWithKI)) {
    $okolje->zunanjaT = [0, 2, 6, 10, 15, 18, 20, 19, 15, 10, 5, 1];
    $okolje->zunanjaVlaga = [84 , 78, 73, 71, 72, 73, 73, 77, 81, 83, 85, 87];
    $okolje->notranjaVlaga = [65 , 65, 65, 65, 65, 65, 65, 65, 65, 65, 65, 65];

    //$okolje->zunanjaT[0] = 0;
    //$okolje->zunanjaVlaga[0] = 84;
    //$okolje->notranjaVlaga[0] = 65;
}

    $konstrukcijaJson = <<<EOT
    {
        "id": "Z1",
        "naziv": "Zunanji zid 1",
        "vrsta": 1,
        "Rsi": 0.25,
        "Rse": 0.04,
        "materiali": [
            {
                "opis": "Les",
                "debelina": 0.015,
                "lambda": 0.14,
                "gostota": 550,
                "difuzijskaUpornost": 70
            },
            {
                "opis": "PVC folija",
                "debelina": 0.0002,
                "lambda": 0.19,
                "gostota": 1200,
                "difuzijskaUpornost": 42000
            },
            {
                "opis": "izolacija",
                "debelina": 0.14,
                "lambda": 0.04,
                "gostota": 50,
                "difuzijskaUpornost": 1
            },
            {
                "opis": "Les",
                "debelina": 0.02,
                "lambda": 0.14,
                "gostota": 550,
                "difuzijskaUpornost": 70
            },
            {
                "opis": "PVC folija",
                "debelina": 0.0002,
                "lambda": 0.19,
                "gostota": 1200,
                "difuzijskaUpornost": 42000
            }
        ]
    }
    EOT;
