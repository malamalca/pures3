<?php
declare(strict_types=1);

namespace App\Core;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

class App
{
    public string $area = 'Pures';
    public static array $allowedActions = [];

    private static ?\App\Core\App $instance = null;
    private array $_vars = [];

    public bool $autoRender = true;

    /**
     * Singleton instance getter
     *
     * @return \App\Core\App
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new App();
        }

        return self::$instance;
    }

    /**
     * Dispatch function
     *
     * @param string $controllerName Controller name
     * @param array $vars Variables
     * @param string $area Area - Pures or Hrup
     * @return void
     */
    public static function dispatch($controllerName, $vars, $area = 'Pures')
    {
        $methodName = $vars['action'] ?? 'index';
        if (isset($vars['action'])) {
            unset($vars['action']);
        }
        $vars = array_map(fn($var) => urldecode($var), $vars);

        $extension = pathinfo($methodName, PATHINFO_EXTENSION);
        if (empty($extension)) {
            $lastParameter = array_slice($vars, -1);
            if (count($lastParameter) == 1) {
                $extension = pathinfo(array_values($lastParameter)[0], PATHINFO_EXTENSION);
            }
        } else {
            $methodName = substr($methodName, 0, -(strlen($extension) + 1));
        }

        if ($controllerName !== 'App') {
            $controllerClass = 'App\Controller\\' . $area . '\\' . $controllerName . 'Controller';
        } else {
            $controllerClass = 'App\Controller\\' . $controllerName . 'Controller';
        }

        // check if action exists
        if (!method_exists($controllerClass, $methodName)) {
            header('HTTP/1.0 404 Not Found');
            echo "Action $controllerName/$methodName Not Found.\n";
            die;
        }

        /** @var \stdClass $controller */
        $controller = new $controllerClass();
        $controller->area = $area;
        /** @var \stdClass $controller->request */
        $controller->request = new \stdClass();
        $controller->request->action = $methodName;
        $controller->request->extension = $extension;

        $func = [$controller, $methodName];
        if (is_callable($func)) {
            $ret = call_user_func_array($func, array_values($vars));
        }

        if (empty($ret)) {
            $view = new View(self::getInstance()->_vars, $controller->viewOptions ?? []);
            $view->area = $area;

            $contents = $view->render($controllerName, $methodName);

            echo $contents;
        }
    }

    /**
     * Set variable for view render
     *
     * @param string|array $varName Variable name or array with variables
     * @param mixed $varValue Variable value
     * @return void
     */
    public static function set($varName, $varValue = null)
    {
        $App = self::getInstance();
        if (is_array($varName)) {
            foreach ($varName as $arrName => $arrValue) {
                $App->_vars[$arrName] = $arrValue;
            }
        } else {
            $App->_vars[$varName] = $varValue;
        }
    }

    /**
     * Build url with specified base
     *
     * @param string $params Url params
     * @param bool $fullBase Full base url.
     * @return string
     */
    public static function url($params, $fullBase = false)
    {
        if (defined('CLI')) {
            $url_base = WWW_ROOT;
            if (substr($params, 0, 14) == '/project-image') {
                $parts = explode('/', $params);
                if (count($parts) == 5) {
                    $image = $parts[4];
                    $projectId = $parts[3];
                    $area = $parts[2];

                    $url_base = self::getProjectFolder($area, $projectId, 'podatki');
                    $params = '/' . $image;
                }
                if (count($parts) == 4) {
                    $image = $parts[3];
                    $projectId = null;
                    $area = $parts[1];

                    $url_base = self::getProjectFolder($area, $projectId, 'podatki');
                    $params = '/' . $image;
                }

                $url_base = 'file:///' . strtr($url_base, DS, '/');
            }
        } else {
            $url_base = (string)Configure::read('App.baseUrl', '/') . '/';
        }

        return $url_base . substr($params, 1);
    }

    /**
     * Function to use in markdown files
     *
     * @param string $fileName Filename
     * @param string $projectId ProjectId
     * @param string $area Area
     * @return string
     */
    public static function projectUrl($fileName, $projectId, $area = 'Pures')
    {
        if (defined('CLI')) {
            if ($projectId) {
                $dir = self::getProjectFolder($area, $projectId, 'podatki');
            } else {
                $dir = WWW_ROOT;
            }

            $fullPath = $dir . $fileName;

            //return 'file:///' . str_replace(DS, '//', $fullPath);
            return $fullPath;
        } else {
            return self::url('/project-image/' . $area . '/' . $projectId . '/' . $fileName);
        }
    }

    /**
     * Send redirect header
     *
     * @param string $dest Redirect destination
     * @return void
     */
    public static function redirect($dest)
    {
        if (!self::isAjax()) {
            header('Location: ' . self::url($dest));
            die;
        }
    }

    /**
     * Determines if request is ajax
     *
     * @return bool
     */
    public static function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            in_array(strtolower($_SERVER['HTTP_X_REQUESTED_WITH']), ['xmlhttprequest']);
    }

    /**
     * Sets flash message
     *
     * @param string $msg Flash message
     * @param string $code Flash code
     * @return void
     */
    public static function setFlash($msg, $code = 'success')
    {
        $_SESSION['flash.message'] = $msg;
        $_SESSION['flash.class'] = $code;
    }

    /**
     * Create flash message for output in html
     *
     * @return void|string
     */
    public static function flash()
    {
        if (!empty($_SESSION['flash.message'])) {
            $msg = $_SESSION['flash.message'];
            $code = $_SESSION['flash.class'];

            unset($_SESSION['flash.message']);
            unset($_SESSION['flash.class']);

            return '<div id="notification" class="' . htmlentities($code) . '">' . htmlentities($msg) . '</div>';
        }
    }

    /**
     * Returns logged user status
     *
     * @return bool
     */
    public static function isLoggedIn()
    {
        return !empty($_SESSION['user']);
    }

    /**
     * Returns if phpures is run for local project
     *
     * @return bool
     */
    public static function isLocalProject()
    {
        return !empty($_ENV['PHPURES_PROJECT']) || !empty($_SERVER['HTTP_PHPURES_PROJECT']);
    }

    /**
     * Returns local project path
     *
     * @return string
     */
    public static function getLocalProjectPath(): string
    {
        if (!empty($_ENV['PHPURES_PROJECT'])) {
            return $_ENV['PHPURES_PROJECT'];
        }
        if (!empty($_SERVER['HTTP_PHPURES_PROJECT'])) {
            return $_SERVER['HTTP_PHPURES_PROJECT'];
        }

        throw new \Exception('Not a local project.');
    }

    /**
     * Vrne lokacijo projekta
     *
     * @param string $area Področje izračuna
     * @param string|null $projectId Id projekta
     * @param string|null $subfolder Podmapa s podaki ali z izračuni
     * @return string
     */
    public static function getProjectFolder(string $area, ?string $projectId, ?string $subfolder = null)
    {
        if (defined('CLI')) {
            if (empty($projectId)) {
                $destFolder = getcwd() . DS;
            } else {
                $destFolder = PROJECTS . $area . DS . $projectId . DS;
            }
        } else {
            if (App::isLocalProject()) {
                $destFolder = App::getLocalProjectPath() . DS;
            } else {
                $destFolder = PROJECTS . $area . DS . $projectId . DS;
            }
        }

        if ($subfolder) {
            $destFolder .= $subfolder . DS;
        }

        return $destFolder;
    }

    /**
     * Vrne datoteko z izvodnimi podatki za izračun.
     *
     * @param string $area Področje izračuna
     * @param string $projectId Id projekta
     * @param string $projectFile Datoteka json
     * @param string $subfolder Podmapa s podaki ali z izračuni
     * @return mixed|null
     */
    public static function loadProjectData($area, $projectId, $projectFile, $subfolder = 'podatki')
    {
        $sourceFolder = self::getProjectFolder($area, $projectId, $subfolder);
        if (!is_dir($sourceFolder)) {
            throw new \Exception(sprintf('Projekt "%s" ne obstaja.', $projectId));
        }

        if (is_dir($sourceFolder . $projectFile)) {
            $dataFilename = $sourceFolder . $projectFile;
            $iterator = new \DirectoryIterator($sourceFolder . $projectFile);
            $jsonObjects = [];
            foreach ($iterator as $info) {
                if ($info->isFile()) {
                    $data = file_get_contents($sourceFolder . $projectFile . DS . (string)$info);
                    if (!$data) {
                        throw new \Exception(sprintf('Datoteke "%s" ni mogoče prebrati.', (string)$info));
                    }
                    $jsonObjects[] = $data;
                }
            }
            $data = '[' . PHP_EOL . implode(', ' . PHP_EOL, $jsonObjects) . PHP_EOL . ']';
        } else {
            $dataFilename = $sourceFolder . $projectFile . '.json';
            if (!file_exists($dataFilename)) {
                //throw new \Exception(sprintf('Datoteka "%s" ne obstaja.', $dataFilename));
                return null;
            } else {
                $data = file_get_contents($dataFilename);
                if (!$data) {
                    throw new \Exception(sprintf('Datoteke "%s" ni mogoče prebrati.', $dataFilename));
                }
            }
        }

        $result = json_decode($data);

        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            //throw new \Exception(sprintf('Datoteka "%s" ni v ustreznem json formatu.', $dataFilename));
            $parser = new JsonParser();
            $result = $parser->lint($data);
            if ($result === null) {
                if (defined('JSON_ERROR_UTF8') && json_last_error() === JSON_ERROR_UTF8) {
                    throw new \UnexpectedValueException('"' . $dataFilename . '" ni v UTF-8, analiza ni možna.');
                }

                return true;
            }

            throw new ParsingException(
                sprintf('%1$s' . "\n" . 'Json napaka: %2$s', $dataFilename, $result->getMessage()),
                $result->getDetails()
            );
        }

        return $result;
    }

    /**
     * Vrne datoteko z izračunom
     *
     * @param string $area Področje izračuna
     * @param string $projectId Id projekta
     * @param string $projectFile Datoteka json
     * @return mixed|null
     */
    public static function loadProjectCalculation($area, $projectId, $projectFile)
    {
        if (substr($projectFile, -1, 1) == DS) {
            $sistemi = [];
            $sourceFolder = self::getProjectFolder($area, $projectId, 'izracuni') . $projectFile;
            if (is_dir($sourceFolder)) {
                $iterator = new \DirectoryIterator($sourceFolder);
                foreach ($iterator as $info) {
                    if ($info->isFile()) {
                        $data = file_get_contents($sourceFolder . (string)$info);
                        if (!$data) {
                            throw new \Exception(sprintf('Datoteke "%s" ni mogoče prebrati.', (string)$info));
                        }

                        $result = json_decode($data);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \Exception(sprintf('Datoteka "%s" ni v ustreznem json formatu.', (string)$info));
                        }

                        if (is_array($result)) {
                            $sistemi = array_merge($sistemi, $result);
                        }
                    }
                }
            }

            return $sistemi;
        }

        return self::loadProjectData($area, $projectId, $projectFile, 'izracuni');
    }

    /**
     * Shrani datoteko z izračunanimi podatki
     *
     * @param string $area Področje izračuna
     * @param string $projectId Id projekta
     * @param string $projectFile Datoteka json
     * @param string|mixed $data Datoteka json
     * @param string $subfolder Podmapa s podaki ali z izračuni
     * @return int<0, max>|false
     */
    public static function saveProjectCalculation($area, $projectId, $projectFile, $data, $subfolder = 'izracuni')
    {
        $destFolder = self::getProjectFolder($area, $projectId, $subfolder);
        $destFilename = $destFolder . $projectFile . '.json';

        if (!is_dir(dirname($destFilename))) {
            mkdir(dirname($destFilename), 0777, true);
        }

        if (!is_string($data)) {
            $data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        $result = file_put_contents($destFilename, $data);

        return $result;
    }
}
