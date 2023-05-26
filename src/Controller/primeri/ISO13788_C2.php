<?php
declare(strict_types=1);

$okolje = new \stdClass();
    $okolje->zunanjaT = ['jan' => -1, 'feb' => 0, 'mar' => 4, 'apr' => 9, 'maj' => 14, 'jun' => 18,
        'jul' => 19, 'avg' => 19, 'sep' => 15, 'okt' => 10, 'nov' => 5, 'dec' => 1];

    $okolje->zunanjaVlaga = ['jan' => 85, 'feb' => 84, 'mar' => 78, 'apr' => 72, 'maj' => 68, 'jun' => 69,
        'jul' => 73, 'avg' => 75, 'sep' => 79, 'okt' => 83, 'nov' => 88, 'dec' => 88];

    // normal occupamcy
    $okolje->notranjaT = ['jan' => 20, 'feb' => 20, 'mar' => 20, 'apr' => 20, 'maj' => 22, 'jun' => 24,
        'jul' => 24.5, 'avg' => 24.5, 'sep' => 22.5, 'okt' => 20, 'nov' => 20, 'dec' => 20];

    $okolje->notranjaVlaga = ['jan' => 39, 'feb' => 40, 'mar' => 44, 'apr' => 49, 'maj' => 54, 'jun' => 58,
        'jul' => 59, 'avg' => 59, 'sep' => 55, 'okt' => 50, 'nov' => 45, 'dec' => 41];

    $konstrukcijaJson = <<<EOT
    {
        "naziv": "Konstrukcija po primeru C.2",
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
                "opis": "Liner",
                "debelina": 0.012,
                "lambda": 0.16,
                "difuzijskaUpornost": 10
            }
        ]
    }
    EOT;
    $konstrukcija = json_decode($konstrukcijaJson);
