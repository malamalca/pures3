<?php
declare(strict_types=1);

namespace App\Lib\Charts;

class PuresChart extends Chart
{
    private array $colorPalette;

    /**
     * Class Constructor
     *
     * @param array $data Podatki za graf
     * @param array $options Dodatne možnosti
     * @return void
     */
    public function __construct($data, $options = [])
    {
        parent::__construct($data, $options);

        $this->colorPalette = [
            '1' => imagecolorallocate($this->chart, 235, 189, 52),
            '2' => imagecolorallocate($this->chart, 235, 125, 52),
            '3' => imagecolorallocate($this->chart, 138, 127, 120),
            '4' => imagecolorallocate($this->chart, 110, 100, 100),
        ];
    }

    /**
     * Class Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        parent::__destruct();

        imagecolordeallocate($this->chart, $this->colorPalette['1']);
        imagecolordeallocate($this->chart, $this->colorPalette['2']);
        imagecolordeallocate($this->chart, $this->colorPalette['3']);
        imagecolordeallocate($this->chart, $this->colorPalette['4']);
    }

    /**
     * Draw series
     *
     * @return void
     */
    protected function drawSeries()
    {
        if (!empty($this->options['layers'])) {
            $offsetX = $this->gridLeft + $this->graphOffsetMargin;
            $sirinaGrafa = $this->gridWidth - 2 * $this->graphOffsetMargin;
            $debelinaKonstrukcije = max($this->data['category']);

            $x1 = 0;
            $x2 = 0;
            $y1 = 0;
            $y2 = 0;
            foreach ($this->options['layers'] as $layer) {
                $x1 = $offsetX;
                $y1 = $this->gridBottom - $this->gridHeight;
                $x2 = $offsetX + $layer['thickness'] / $debelinaKonstrukcije * $sirinaGrafa;
                $y2 = $this->gridBottom - 1;

                imagefilledrectangle(
                    $this->chart,
                    (int)$x1,
                    (int)$y1,
                    (int)$x2,
                    (int)$y2,
                    $this->colorPalette[$layer['color']] ?? $this->gridColor
                );

                /* Linija med sloji */
                imageline($this->chart, (int)$x1, (int)$y1, (int)$x1, (int)$y2, $this->separatorLineColor);

                // Draw the label
                $labelBox = imagettfbbox($this->fontSize, 0, $this->font, $layer['title']);
                if ($labelBox) {
                    $labelWidth = $labelBox[4] - $labelBox[0];

                    $labelX = ($x1 + $x2) / 2 - $labelWidth / 2;
                    $labelY = $this->gridBottom + $this->gridLabelMargin + $this->fontSize;

                    imagettftext(
                        $this->chart,
                        $this->fontSize,
                        0,
                        (int)$labelX,
                        $labelY,
                        $this->labelColor,
                        $this->font,
                        $layer['title']
                    );
                }

                $offsetX += $layer['thickness'] / $debelinaKonstrukcije * $sirinaGrafa;
            }

            /* Zadnja Linija med sloji */
            imageline($this->chart, (int)$x2, (int)$y1, (int)$x2, (int)$y2, $this->separatorLineColor);
        }
        parent::drawSeries();
    }
}
