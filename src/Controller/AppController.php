<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class AppController
{
    public string $area;
    public \stdClass $request;
    /**
     * Prikaz podatkov o ovoju cone
     *
     * @param string $conaId Id cone
     * @param string $projectId Building name
     * @param string $fileName Image filename
     * @return never
     */
    public function projectImage($conaId, $projectId, $fileName)
    {
        $dir = App::getProjectFolder($conaId, $projectId);
        $fullPath = $dir . 'podatki' . DS . $fileName;

        if (file_exists($fullPath)) {
            $fullPath = realpath($fullPath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE); // Return MIME type a la the 'mimetype' extension
            if ($finfo && $fullPath) {
                $mime = finfo_file($finfo, $fullPath);

                header('Content-Type: ' . $mime);
                readfile($fullPath);
            }
            die;
        } else {
            throw new \Exception('Project image not found');
        }
    }
}
