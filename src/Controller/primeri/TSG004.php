<?php
    declare(strict_types=1);

    $inputZunanjaT = ['jan' => -1, 'feb' => 1, 'mar' => 6, 'apr' => 10, 'maj' => 15, 'jun' => 18,
        'jul' => 20, 'avg' => 19, 'sep' => 15, 'okt' => 10, 'nov' => 4, 'dec' => 1];

    $inputZunanjaVlaga = ['jan' => 82 , 'feb' => 77, 'mar' => 72, 'apr' => 71, 'maj' => 73, 'jun' => 72,
        'jul' => 75, 'avg' => 76, 'sep' => 80, 'okt' => 82, 'nov' => 84, 'dec' => 85];

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
