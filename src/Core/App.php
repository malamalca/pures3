<?php
declare(strict_types=1);

namespace App\Core;

class App
{
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
     * @return void
     */
    public static function dispatch($controllerName, $vars)
    {
        $methodName = $vars['action'] ?? 'index';
        if (isset($vars['action'])) {
            unset($vars['action']);
        }
        $vars = array_map(fn($var) => urldecode($var), $vars);

        $controllerClass = 'App\Controller\\' . $controllerName . 'Controller';

        // check if action exists
        if (!method_exists($controllerClass, $methodName)) {
            header('HTTP/1.0 404 Not Found');
            echo "Action $controllerName/$methodName Not Found.\n";
            die;
        }

        $controller = new $controllerClass();

        $func = [$controller, $methodName];
        if (is_callable($func)) {
            $ret = call_user_func_array($func, array_values($vars));
        }

        if (empty($ret)) {
            $view = new View(self::getInstance()->_vars, $controller->viewOptions ?? []);

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
     * @return string
     */
    public static function url($params)
    {
        $url_base = Configure::read('App.baseUrl', '/') . '/';

        return $url_base . substr($params, 1);
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
            in_array(strtolower($_SERVER['HTTP_X_REQUESTED_WITH']), ['xmlhttprequest', 'thorbell']);
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
     * Vrne lokacijo projekta
     *
     * @param string $projectId Id projekta
     * @param string|null $subfolder Podmapa s podaki ali z izračuni
     * @return string
     */
    public static function getProjectFolder($projectId, $subfolder = null)
    {
        if (defined('CLI')) {
            if (empty($projectId)) {
                $destFolder = getcwd() . DS;
            } else {
                $destFolder = PROJECTS . $projectId . DS;
            }
        } else {
            $destFolder = PROJECTS . $projectId . DS;
        }

        if ($subfolder) {
            $destFolder .= $subfolder . DS;
        }

        return $destFolder;
    }

    /**
     * Vrne datoteko z izvodnimi podatki za izračun.
     *
     * @param string $projectId Id projekta
     * @param string $projectFile Datoteka json
     * @param string $subfolder Podmapa s podaki ali z izračuni
     * @return mixed|null
     */
    public static function loadProjectData($projectId, $projectFile, $subfolder = 'podatki')
    {
        $sourceFolder = self::getProjectFolder($projectId, $subfolder);
        if (!is_dir($sourceFolder)) {
            throw new \Exception(sprintf('Projekt "%s" ne obstaja.', $projectId));
        }

        $dataFilename = $sourceFolder . $projectFile . '.json';

        if (!file_exists($dataFilename)) {
            //throw new \Exception(sprintf('Datoteka "%s" ne obstaja.', $dataFilename));
            return null;
        } else {
            $data = file_get_contents($dataFilename);
            if (!$data) {
                throw new \Exception(sprintf('Datoteke "%s" ni mogoče prebrati.', $dataFilename));
            }

            $result = json_decode($data);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(sprintf('Datoteka "%s" ni v ustreznem json formatu.', $dataFilename));
            }

            return $result;
        }
    }

    /**
     * Vrne datoteko z izračunom
     *
     * @param string $projectId Id projekta
     * @param string $projectFile Datoteka json
     * @return mixed|null
     */
    public static function loadProjectCalculation($projectId, $projectFile)
    {
        if (substr($projectFile, -1, 1) == DS) {
            $sourceFolder = self::getProjectFolder($projectId, 'izracuni') . $projectFile;
            $sistemi = [];
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

            return $sistemi;
        }

        return self::loadProjectData($projectId, $projectFile, 'izracuni');
    }

    /**
     * Shrani datoteko z izračunanimi podatki
     *
     * @param string $projectId Id projekta
     * @param string $projectFile Datoteka json
     * @param string|mixed $data Datoteka json
     * @param string $subfolder Podmapa s podaki ali z izračuni
     * @return int<0, max>|false
     */
    public static function saveProjectCalculation($projectId, $projectFile, $data, $subfolder = 'izracuni')
    {
        $destFolder = self::getProjectFolder($projectId, $subfolder);
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
