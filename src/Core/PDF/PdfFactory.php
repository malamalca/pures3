<?php
declare(strict_types=1);

namespace App\Core\PDF;

class PdfFactory
{
    /**
     * Create pdf class
     *
     * @param string $engine PDF Engine
     * @param array $enigneOptions Engine options
     * @return \App\Core\PDF\TCPDFEngine|\App\Core\PDF\WKHTML2PDFEngine
     */
    public static function create($engine, $enigneOptions)
    {
        switch ($engine) {
            case 'TCPDF':
                return new TCPDFEngine($enigneOptions);
            case 'WKHTML2PDF':
                return new WKHTML2PDFEngine($enigneOptions);
            default:
                throw new \Exception('Invalid Engine');
        }
    }
}
