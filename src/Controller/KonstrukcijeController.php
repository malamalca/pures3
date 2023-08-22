<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class KonstrukcijeController
{
    /**
     * Prikaz podatkov o konstrukciji
     *
     * @param string $projectName Building name
     * @param string $konsId Konstruction id
     * @return void
     */
    public function view($projectName, $konsId)
    {
        App::set('okolje', App::loadProjectCalculation($projectName, 'okolje'));

        $ntKonsArray = App::loadProjectCalculation($projectName, 'konstrukcije' . DS . 'netransparentne');
        $kons = array_first($ntKonsArray, fn($item) => $item->id == $konsId);

        if (empty($kons)) {
            throw new \Exception('Konstrukcija ne obstaja');
        }

        App::set('kons', $kons);
    }

    /**
     * Prikaz izračun U vrednosti
     *
     * @param string|null $konsId Konstruction id
     * @return void
     */
    public function u($konsId = null)
    {
        $data = <<<EOT

        EOT;
        App::set('data', $data);
    }

    /**
     * Prikaz slike grafa
     *
     * @return void
     */
    public function graf()
    {
        // podatki o temperaturah na posameznem stiku slojev -  T_Si + T_[n] + T_se
        $data = [20, 19.8, 19, -11, -12.8, -13];

        // podatki o slojih konstrukcije: naziv | debelina | lambda
        $sloji = [['ab', 0.2, 2], ['eps', 0.2, 0.04], ['fasada', 0.01, 0.7]];

        // Image dimensions
        $imageWidth = 600;
        $imageHeight = 400;

        // Grid dimensions and placement within image
        $gridTop = 40;
        $gridLeft = 50;
        $gridBottom = $imageHeight - 40;
        $gridRight = $imageWidth - 50;
        $gridHeight = $gridBottom - $gridTop;
        $gridWidth = $gridRight - $gridLeft;

        // Bar and line width
        $lineWidth = 1;
        $datalineWidth = 3;
        $barWidth = 20;

        // Font settings
        $font = RESOURCES . 'OpenSans-Regular.ttf';
        $fontSize = 8;

        // Margin between label and axis
        $labelMargin = 8;

        // Margin between axis and graph
        $offsetMargin = 20;

        // Max value on y-axis
        $yMaxValue = max($data);
        $yMaxAxis = $yMaxValue + 0.1 * $yMaxValue;
        $yMinValue = min($data);
        $yMinAxis = $yMinValue + 0.1 * $yMinValue;

        // calculate chart lines on y-axis
        //var_dump(($yMaxValue - $yMinValue) / 5);

        // Distance between grid lines on y-axis
        $yGridStep = 10;

        $yGridLines = [];
        $yGridLines[] = ceil($yMinAxis / $yGridStep) * $yGridStep;
        $yGridLinesCount = floor(($yMaxAxis - $yGridLines[0]) / $yGridStep);
        
        for ($i = 0; $i < $yGridLinesCount; $i++) {
            $yGridLines[] = $yGridLines[sizeof($yGridLines) - 1] + $yGridStep;
        }

        // Init image
        $chart = imagecreatetruecolor($imageWidth, $imageHeight);

        // Setup colors
        $backgroundColor = imagecolorallocate($chart, 255, 255, 255);
        $axisColor = imagecolorallocate($chart, 85, 85, 85);
        $labelColor = $axisColor;
        $gridColor = imagecolorallocate($chart, 212, 212, 212);
        $barColor = imagecolorallocatealpha($chart, 133, 133, 133, 50);
        $separatorLineColor = imagecolorallocate($chart, 80, 80, 80);
        $dataLineColor = imagecolorallocate($chart, 255, 110, 30);

        imagefill($chart, 0, 0, $backgroundColor);
        imagesetthickness($chart, $lineWidth);

        /*
        * Print grid lines bottom up
        */
        //for ($i = 0; $i <= $yMaxValue; $i += $yLabelSpan) {
        foreach ($yGridLines as $yGridLineValue) {
            //$y = $gridBottom - $i * $gridHeight / $yMaxAxis;

            $y = $gridBottom - ($yGridLineValue - $yMinAxis) / ($yMaxAxis - $yMinAxis) * $gridHeight;

            // draw the line
            imageline($chart, $gridLeft, (int)$y, $gridRight, (int)$y, $gridColor);

            // draw right aligned label
            $labelBox = imagettfbbox($fontSize, 0, $font, strval($yGridLineValue));
            $labelWidth = $labelBox[4] - $labelBox[0];

            $labelX = $gridLeft - $labelWidth - $labelMargin;
            $labelY = $y + $fontSize / 2;

            imagettftext($chart, $fontSize, 0, (int)$labelX, (int)$labelY, $labelColor, $font, strval($yGridLineValue));
        }

        /*
        * Draw x- and y-axis
        */
        imageline($chart, $gridLeft, $gridTop, $gridLeft, $gridBottom, $axisColor);
        imageline($chart, $gridLeft, $gridBottom, $gridRight, $gridBottom, $axisColor);

        /*
        * Draw the bars with labels
        */
        $debelinaKonstrukcije = array_sum(array_column($sloji, 1));
        
        $sirinaGrafa = $gridWidth - 2 * $offsetMargin;

        $offsetX = $gridLeft + $offsetMargin;

        foreach ($sloji as $sloj) {
            $nazivSloja = $sloj[0];
            $debelinaSloja = $sloj[1];
            $lambdaSloja = $sloj[2];

            $x1 = $offsetX;
            $y1 = $gridBottom - $gridHeight;
            $x2 = $offsetX + $debelinaSloja / $debelinaKonstrukcije * $sirinaGrafa;
            $y2 = $gridBottom - 1;

            imagefilledrectangle($chart, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $barColor);

            /* Linija med sloji */
            imageline($chart, (int)$x1, (int)$y1, (int)$x1, (int)$y2, $separatorLineColor);

            // Draw the label
            $labelBox = imagettfbbox($fontSize, 0, $font, $nazivSloja);
            $labelWidth = $labelBox[4] - $labelBox[0];

            $labelX = ($x1 + $x2) / 2 - $labelWidth / 2;
            $labelY = $gridBottom + $labelMargin + $fontSize;

            imagettftext($chart, $fontSize, 0, (int)$labelX, $labelY, $labelColor, $font, $nazivSloja);

            $offsetX += $debelinaSloja / $debelinaKonstrukcije * $sirinaGrafa;
        }

        /* Linija med sloji (zadnja) */
        imageline($chart, (int)$x2, (int)$y1, (int)$x2, (int)$y2, $separatorLineColor);

        /** Draw the data (temperature) axis line */
        imagesetthickness($chart, $datalineWidth);

        $dataX = [];

        /** First data point for external temperature */
        $offsetX = $gridLeft + $offsetMargin;

        $dataX[] = $offsetX - $offsetMargin / 2;
        $dataX[] = $offsetX;
        foreach ($sloji as $sloj) {
            $debelinaSloja = $sloj[1];

            $dataX[] = $offsetX + $debelinaSloja / $debelinaKonstrukcije * $sirinaGrafa;
            $offsetX += $debelinaSloja / $debelinaKonstrukcije * $sirinaGrafa;
        }
        $dataX[] = $offsetX + $offsetMargin / 2;

        foreach ($dataX as $k => $value) {
            if ($k > 0) {
                $x1 = $prevValue;
                $y1 = $gridBottom - ($data[$k - 1] - $yMinAxis) / ($yMaxAxis - $yMinAxis) * $gridHeight;
                $x2 = $value;
                $y2 = $gridBottom - ($data[$k] - $yMinAxis) / ($yMaxAxis - $yMinAxis) * $gridHeight;

                imageline($chart, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $dataLineColor);
            }
            $prevValue = $value;
        }

        /*
        * Output image to browser
        */

        header('Content-Type: image/png');
        imagepng($chart);
        imagedestroy($chart);
        die;
    }
}
