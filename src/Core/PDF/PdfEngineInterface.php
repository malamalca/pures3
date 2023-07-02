<?php
declare(strict_types=1);

namespace App\Core\PDF;

interface PdfEngineInterface
{
    /**
     * Constructor
     *
     * @param array $enigneOptions Array of options.
     * @return void
     */
    public function __construct($enigneOptions);

    /**
     * Sets or returns object's options
     *
     * @param array $values Options values.
     * @return mixed
     */
    public function options($values = null);

    /**
     * Add a new HTML page to PDF
     *
     * @param string $html Options values.
     * @param array $options Options array.
     * @return mixed
     */
    public function newPage($html, $options = []);

    /**
     * Saves PDF to a file
     *
     * @param string $filename Options values.
     * @return mixed
     */
    public function saveAs($filename);
}
