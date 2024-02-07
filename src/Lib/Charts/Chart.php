<?php
declare(strict_types=1);

namespace App\Lib\Charts;

use GdImage;

class Chart
{
    protected const CHART_WIDTH = 800;
    protected const CHART_HEIGHT = 600;
    protected const OFFSET_TOP = 10;
    protected const OFFSET_BOTTOM = 40;
    protected const OFFSET_LEFT = 50;
    protected const OFFSET_RIGHT = 50;

    protected const SERIES_COLOR = 'FF0000';

    protected array $data = [];
    protected array $options = [];
    protected ?GdImage $chart;

    protected int $chartWidth;
    protected int $chartHeight;

    protected int $gridTop;
    protected int $gridLeft;
    protected int $gridBottom;
    protected int $gridRight;
    protected int $gridHeight;
    protected int $gridWidth;

    // Bar and line width
    protected int $gridLineWidth = 1;
    protected int $dataLineWidth = 4;

    // Font settings
    protected string $font = RESOURCES . 'OpenSans-Regular.ttf';
    protected int $fontSize = 12;

    // Margin between label and axis
    protected int $gridLabelMargin = 8;
    // Margin between axis and graph
    protected int $graphOffsetMargin = 20;

    protected int $backgroundColor;
    protected int $axisColor;
    protected int $labelColor;
    protected int $gridColor;
    protected int $separatorLineColor;

    /**
     * Class Constructor
     *
     * @param array $data Podatki za graf
     * @param array $options Dodatne možnosti
     * @return void
     */
    public function __construct($data, $options = [])
    {
        $this->options = $options;
        $this->data = $data;

        $this->chartWidth = self::CHART_WIDTH;
        $this->chartHeight = self::CHART_HEIGHT;

        // Init image
        $chart = imagecreatetruecolor($this->chartWidth, $this->chartHeight);
        if (!$chart) {
            throw new \Exception('GD Image Create Failed.');
        }
        $this->chart = $chart;

        // Grid dimensions and placement within image
        $this->gridTop = self::OFFSET_TOP;
        $this->gridLeft = self::OFFSET_LEFT;
        $this->gridBottom = $this->chartHeight - self::OFFSET_BOTTOM;
        $this->gridRight = $this->chartWidth - self::OFFSET_RIGHT;
        $this->gridHeight = $this->gridBottom - $this->gridTop;
        $this->gridWidth = $this->gridRight - $this->gridLeft;

        // Setup colors
        $this->backgroundColor = (int)imagecolorallocate($this->chart, 255, 255, 255);
        $this->axisColor = (int)imagecolorallocate($this->chart, 85, 85, 85);
        $this->labelColor = $this->axisColor;
        $this->gridColor = (int)imagecolorallocate($this->chart, 212, 212, 212);
        $this->separatorLineColor = (int)imagecolorallocate($this->chart, 80, 80, 80);
    }

    /**
     * Class Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        imagecolordeallocate($this->chart, $this->backgroundColor);
        imagecolordeallocate($this->chart, $this->axisColor);
        imagecolordeallocate($this->chart, $this->labelColor);
        imagecolordeallocate($this->chart, $this->gridColor);
        imagecolordeallocate($this->chart, $this->separatorLineColor);

        imagedestroy($this->chart);
    }

    /**
     * Draws chart
     *
     * @return string
     */
    public function draw()
    {
        $this->drawAxes();
        $this->drawBackground();
        $this->drawHorizontalGrid();
        $this->drawSeries();

        ob_start();
        imagepng($this->chart);
        $imageData = (string)ob_get_contents();
        ob_end_clean();

        return $imageData;
    }

    /**
     * Draw background
     *
     * @return void
     */
    protected function drawBackground()
    {
        imagefill($this->chart, 0, 0, $this->backgroundColor);
    }

    /**
     * Draw x- and y-axis
     *
     * @return void
     */
    protected function drawAxes()
    {
        imageline($this->chart, $this->gridLeft, $this->gridTop, $this->gridLeft, $this->gridBottom, $this->axisColor);
        imageline(
            $this->chart,
            $this->gridLeft,
            $this->gridBottom,
            $this->gridRight,
            $this->gridBottom,
            $this->axisColor
        );
    }

    /**
     * Draw horizontal grid
     *
     * @return void
     */
    protected function drawHorizontalGrid()
    {
        // Max value on y-axis
        $maxSeriesValues = [];
        $minSeriesValues = [];

        foreach ($this->data['series'] as $k => $serie) {
            $maxSeriesValues[$k] = max(array_diff($serie, [null]));
            $minSeriesValues[$k] = min(array_diff($serie, [null]));
        }
        $yMaxValue = max($maxSeriesValues);
        $yMinValue = min($minSeriesValues);

        $yMaxAxis = $yMaxValue + abs(0.05 * ($yMaxValue - $yMinValue));
        $yMinAxis = $yMinValue - abs(0.05 * ($yMaxValue - $yMinValue));

        // Distance between grid lines on y-axis
        // $yGridStep = 10;
        // Number of lines we want in a grid
        $yGridLinesCount = 4;
        $gridSteps = [1, 2, 5];
        $yGridStepReal = ($yMaxValue - $yMinValue) / $yGridLinesCount;

        $yGridFactor = 1;
        while ($yGridStepReal < 10) {
            $yGridStepReal *= 10;
            $yGridFactor *= 0.1;
        }
        while ($yGridStepReal > 100) {
            $yGridStepReal /= 10;
            $yGridFactor *= 10;
        }

        $yGridStepReal = ((int)$yGridStepReal) / 10;
        $yGridStep = null;
        $yGridMinDifference = 10;
        foreach ($gridSteps as $gridStep) {
            if (abs($gridStep - $yGridStepReal) < $yGridMinDifference) {
                $yGridMinDifference = abs($gridStep - $yGridStepReal);
                $yGridStep = $gridStep;
            }
        }

        $yGridStep = $yGridStep * $yGridFactor * 10;

        $yGridLines = [];
        $yGridStepDiff = ($yMaxValue - $yMinValue) / $yGridLinesCount;

        // First horizontal grid line
        $firstGridLinePos = floor($yMinAxis / $yGridStep) * $yGridStep;
        if ($firstGridLinePos > $this->gridBottom) {
            $yGridLines[] = $firstGridLinePos;
        } else {
            $yGridLines[] = $firstGridLinePos + $yGridStep;
        }

        $yGridLinesCount = floor(($yMaxAxis - $firstGridLinePos) / $yGridStep);

        for ($i = 0; $i < $yGridLinesCount; $i++) {
            $yGridLines[] = $yGridLines[count($yGridLines) - 1] + $yGridStep;
        }

        imagesetthickness($this->chart, $this->gridLineWidth);

        /*
        * Print grid lines bottom up
        */
        foreach ($yGridLines as $yGridLineValue) {
            $y = $this->gridBottom - ($yGridLineValue - $yMinAxis) / ($yMaxAxis - $yMinAxis) * $this->gridHeight;

            // draw the line
            imageline($this->chart, $this->gridLeft, (int)$y, $this->gridRight, (int)$y, $this->gridColor);

            // draw right aligned label
            $labelBox = imagettfbbox($this->fontSize, 0, $this->font, strval($yGridLineValue));
            if ($labelBox) {
                $labelWidth = $labelBox[4] - $labelBox[0];

                $labelX = $this->gridLeft - $labelWidth - $this->gridLabelMargin;
                $labelY = $y + $this->fontSize / 2;

                imagettftext(
                    $this->chart,
                    $this->fontSize,
                    0,
                    (int)$labelX,
                    (int)$labelY,
                    $this->labelColor,
                    $this->font,
                    strval($yGridLineValue)
                );
            }
        }
    }

    /**
     * Draw series
     *
     * @return void
     */
    protected function drawSeries()
    {
        // Calculate X-scale
        $sirinaGrafa = $this->gridWidth - 2 * $this->graphOffsetMargin;
        $offsetX = $this->gridLeft + $this->graphOffsetMargin;
        $scaleX = $sirinaGrafa / max($this->data['category']);

        // Calculate Y-scale
        // Max value on y-axis
        $maxSeriesValues = [];
        $minSeriesValues = [];

        foreach ($this->data['series'] as $k => $serie) {
            $maxSeriesValues[$k] = max(array_diff($serie, [null]));
            $minSeriesValues[$k] = min(array_diff($serie, [null]));
        }
        $yMaxValue = max($maxSeriesValues);
        $yMinValue = min($minSeriesValues);

        $yMaxAxis = $yMaxValue + abs(0.05 * ($yMaxValue - $yMinValue));
        $yMinAxis = $yMinValue - abs(0.05 * ($yMaxValue - $yMinValue));

        $scaleY = $this->gridHeight / ($yMaxAxis - $yMinAxis);

        $equalSpacing = $this->options['equalSpacing'] ?? false;

        $prevLabelX = $this->gridLeft;
        $prevGridX = 0;

        // allocate series colors
        $seriesColors = [];
        foreach ($this->data['series'] as $k => $serie) {
            $seriesColors[$k] = $this->allocateColor($this->options['seriesColor'][$k] ?? self::SERIES_COLOR);
        }

        foreach ($this->data['category'] as $k => $value) {
            if ($equalSpacing) {
                $gridX = $offsetX + ($sirinaGrafa / count($this->data['category'])) * $k;
            } else {
                $gridX = $offsetX + $value * $scaleX;
            }
            if ($k > 0) {
                // draw series line
                foreach ($this->data['series'] as $serieId => $serie) {
                    // do not display point when serie value is null for current x value
                    if (!is_null($serie[$k - 1]) && !is_null($serie[$k])) {
                        $x1 = $prevGridX;
                        $y1 = $this->gridBottom - ($serie[$k - 1] - $yMinAxis) * $scaleY;
                        $x2 = $gridX;
                        $y2 = $this->gridBottom - ($serie[$k] - $yMinAxis) * $scaleY;

                        imagesetthickness(
                            $this->chart,
                            $this->options['seriesThickness'][$serieId] ?? $this->dataLineWidth
                        );
                        imageline($this->chart, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $seriesColors[$serieId]);
                    }
                }

                /* Grid Line */
                if (!isset($this->options['showCategoryLines']) || $this->options['showCategoryLines'] == true) {
                    $x1 = $gridX;
                    $y1 = $this->gridBottom - $this->gridHeight;
                    $x2 = $gridX;
                    $y2 = $this->gridBottom - 1;

                    imagefilledrectangle(
                        $this->chart,
                        (int)$x1,
                        (int)$y1,
                        (int)$x2,
                        (int)$y2,
                        $this->gridColor
                    );
                }

                if (!isset($this->options['showCategoryValues']) || $this->options['showCategoryValues'] == true) {
                    // draw right aligned label below x-axis
                    $labelBox = imagettfbbox(
                        $this->fontSize,
                        0,
                        $this->font,
                        strval(round($this->data['category'][$k], 0))
                    );
                    if ($labelBox) {
                        $labelWidth = $labelBox[4] - $labelBox[0];

                        $labelX = $gridX - $labelWidth / 2;
                        $labelY = $this->gridBottom + $this->fontSize + $this->gridLabelMargin;

                        // prevent overlap
                        if ($labelX > $prevLabelX) {
                            imagettftext(
                                $this->chart,
                                $this->fontSize,
                                0,
                                (int)$labelX,
                                (int)$labelY,
                                $this->labelColor,
                                $this->font,
                                strval(round($this->data['category'][$k], 0))
                            );
                            $prevLabelX = $labelX + $labelWidth;
                        }
                    }
                }
            }
            $prevGridX = $gridX;
        }
    }

    /**
     * Allocate color
     *
     * @param string $RGB Rgb color
     * @return int
     */
    private function allocateColor(string $RGB)
    {
        return (int)imagecolorallocate(
            $this->chart,
            (int)hexdec(substr($RGB, 0, 2)),
            (int)hexdec(substr($RGB, 2, 2)),
            (int)hexdec(substr($RGB, 4, 2))
        );
    }
}
