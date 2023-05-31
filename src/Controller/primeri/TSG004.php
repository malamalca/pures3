<?php
    declare(strict_types=1);

    $inputZunanjaT = [-1, 1, 6, 10, 15, 18, 20, 19, 15, 10, 4, 1];
    $inputZunanjaVlaga = [82 , 77, 72, 71, 73, 72, 75, 76, 80, 82, 84, 85];

    $okolje = \App\Lib\CalcOkolje::notranjeOkolje(['zunanjaT' => $inputZunanjaT, 'zunanjaVlaga' => $inputZunanjaVlaga]);

    // compare with KI
if (!empty($compareWithKI)) {
    $okolje->zunanjaT['jan'] = 0;
    $okolje->zunanjaVlaga['jan'] = 84;
    $okolje->notranjaVlaga['jan'] = 65;
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
                "opis": "a",
                "debelina": 0.15,
                "lambda": 2.04,
                "gostota": 2400,
                "difuzijskaUpornost": 9
            },
            {
                "opis": "b",
                "debelina": 0.15,
                "lambda": 0.041,
                "gostota": 20,
                "difuzijskaUpornost": 5.25
            },
            {
                "opis": "c",
                "debelina": 0.15,
                "lambda": 2.04,
                "gostota": 2400,
                "difuzijskaUpornost": 9
            }
        ]
    }
    EOT;
