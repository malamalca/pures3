<?php
declare(strict_types=1);

namespace App\Core\PDF;

use mikehaertl\wkhtmlto\Pdf;

class WKHTML2PDFEngine extends Pdf implements PdfEngineInterface
{
    /**
     * PDF options
     *
     * @var array
     */
    private array $_localOptions = [];

    private array $_defaultOptions = [
        'binary' => 'C:\bin\wkhtmltopdf\bin\wkhtmltopdf.exe',
        'enable-local-file-access',
        'no-outline', // Make Chrome not complain
        'print-media-type',
        'page-size' => 'A4',
        'margin-top' => 10,
        'margin-right' => 10,
        'margin-bottom' => 10,
        'margin-left' => 10,
        'background',

        // Default page options
        'disable-smart-shrinking',

        //'user-style-sheet' => WWW_ROOT . 'css' . DS . 'main.css',
    ];

    private array $_tempFiles = [];

    /**
     * __construct
     *
     * @param array $enigneOptions Array of options.
     * @return void
     */
    public function __construct($enigneOptions)
    {
        unset($enigneOptions['layout']);
        $this->options(array_replace_recursive($this->_defaultOptions, $enigneOptions));
        $options = $this->options();
        parent::__construct($options);

        if (!empty($enigneOptions['headerHtml'])) {
            $this->setHeaderHtml($enigneOptions['headerHtml']);
        }
        if (!empty($enigneOptions['footerHtml'])) {
            $this->setFooterHtml($enigneOptions['footerHtml']);
        }
    }

    /**
     * __destruct
     *
     * @return void
     */
    public function __destruct()
    {
        foreach ($this->_tempFiles as $fileName) {
            //unlink($fileName);
        }
        //parent::__destruct();
    }

    /**
     * Save PDF as file.
     *
     * @param string $filename Filename.
     * @return bool
     */
    public function saveAs($filename)
    {
        $result = parent::saveAs($filename);

        if (!$result) {
            throw new \Exception(parent::getError());
        }

        return $result;
    }

    /**
     * Add page with html contents
     *
     * @param string $html Html page content.
     * @param array $options Page options.
     * @return void
     */
    public function newPage($html, $options = [])
    {
        $fileName = TMP . uniqid('', true) . '.html';
        file_put_contents($fileName, $html);
        if (file_exists($fileName)) {
            $this->addPage($fileName);
            $this->_tempFiles[] = $fileName;
        } else {
            die('No File');
        }
    }

    /**
     * Get last error.
     *
     * @return string
     */
    public function getError()
    {
        return parent::getError();
    }

    /**
     * Returns image typ
     *
     * @param string $binary Binary data
     * @return string|bool
     */
    private function getImageType($binary)
    {
        $types = [
            'jpeg' => "\xFF\xD8\xFF",
            'gif' => 'GIF',
            'png' => "\x89\x50\x4e\x47\x0d\x0a",
        ];

        $found = false;
        foreach ($types as $type => $header) {
            if (strpos($binary, $header) === 0) {
                $found = $type;
                break;
            }
        }

        return $found;
    }

    /**
     * Set page header html.
     *
     * @param string $html Html page content.
     * @return void
     */
    public function setHeaderHtml($html)
    {
        if (substr($html, 0, 2) == '{"') {
            $data = json_decode($html, true);
            if ($data) {
                $binary = base64_decode($data['image']);
                $type = $this->getImageType($binary);
                if ($type) {
                    $html = '<img src="data:image/' . $type . ';base64,' . $data['image'] . '" />';
                }
            }
        }

        $this->setOptions(['header-html' => '<!doctype html><head>' .
                            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>' .
                            '<html><body><div id="header">' .
                            $html .
                            '</div></body></html>']);
    }

    /**
     * Set page footer html.
     *
     * @param string $html Html page content.
     * @return void
     */
    public function setFooterHtml($html)
    {
        if (substr($html, 0, 2) == '{"') {
            $data = json_decode($html, true);
            if ($data) {
                $binary = base64_decode($data['image']);
                $type = $this->getImageType($binary);
                if ($type) {
                    $html = '<img src="data:image/' . $type . ';base64,' . $data['image'] . '" />';
                }
            }
        }

        $this->setOptions(['footer-html' => '<!doctype html><head>' .
                            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>' .
                            '<html><body><div id="footer">' .
                            $html .
                            '</div></body></html>']);
    }

    /**
     * Get/set options.
     *
     * @param array $values Options values.
     * @return mixed
     */
    public function options($values = null)
    {
        if ($values === null) {
            return $this->_localOptions;
        }
        $this->_localOptions = $values;

        return $this;
    }
}
