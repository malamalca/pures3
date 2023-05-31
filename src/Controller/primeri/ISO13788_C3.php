<?php
declare(strict_types=1);

$okolje = new \stdClass();
    $okolje->zunanjaT = [-1, 0, 4, 9, 14, 18, 19, 19, 15, 10, 5, 1];

    $okolje->zunanjaVlaga = [85, 84, 78, 72, 68, 69, 73, 75, 79, 83, 88, 88];

    // normal occupamcy
    $okolje->notranjaT = [20, 20, 20, 20, 22, 24, 24.5, 24.5, 22.5, 20, 20, 20];

    $okolje->notranjaVlaga = [39, 40, 44, 49, 54, 58, 59, 59, 55, 50, 45, 41];

    $konstrukcijaJson = <<<EOT
    {
        "naziv": "Konstrukcija po primeru C.3",
        "Rsi": 0.25,
        "Rse": 0.04,
        "materiali": [
            {
                "opis": "Weatherproofing",
                "debelina": 0.01,
                "lambda": 0.2,
                "difuzijskaUpornost": 500000
            },
            {
                "opis": "Insulation",
                "debelina": 0.1,
                "lambda": 0.033333333333333333,
                "difuzijskaUpornost": 150
            },
            {
                "opis": "Vapour check",
                "Sd": 1000
            },
            {
                "opis": "Insulation",
                "debelina": 0.1,
                "lambda": 0.033333333333333333,
                "difuzijskaUpornost": 150
            },
            {
                "opis": "Liner",
                "debelina": 0.012,
                "lambda": 0.16,
                "difuzijskaUpornost": 10
            }
        ]
    }
    EOT;
    $konstrukcija = json_decode($konstrukcijaJson);
