<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    /**
     * @var array $_vars
     */
    private $_vars = null;

    /**
     * Constructor
     *
     * @param array $vars Passed variables
     */
    public function __construct(array $vars = null)
    {
        $this->_vars = $vars;
    }

    /**
     * Render function
     *
     * @param string $controllerName Controller name
     * @param string $methodName Method name
     * @return void
     */
    public function render($controllerName, $methodName)
    {
        $App = App::getInstance();

        $templatePath = TEMPLATES . $controllerName . DS;
        if ($App::isAjax()) {
            $templatePath .= 'ajax' . DS;
        }
        $templateFile = realpath($templatePath . $methodName . '.php');

        if (
            empty($templateFile) ||
            strpos($templateFile, $templatePath) !== 0
        ) {
            die(sprintf('Template "%s" does not exist', $templatePath . $methodName . '.php'));
        }

        extract($this->_vars);

        ob_start();
        include $templateFile;
        $contents = ob_get_contents();
        ob_end_clean();

        // extract vars that might be set in template
        extract($this->_vars);

        // set default title
        if (!isset($title)) {
            $title = $controllerName . '::' . $methodName;
        }

        // output render data
        if ($App::isAjax()) {
            require TEMPLATES . 'layouts' . DS . 'ajax.php';
        } else {
            require TEMPLATES . 'layouts' . DS . 'default.php';
        }
    }

    /**
     * Set variable for view render
     *
     * @param string|array $varName Variable name or array with variables
     * @param mixed $varValue Variable value
     * @return void
     */
    public function set($varName, $varValue = null)
    {
        if (is_array($varName)) {
            foreach ($varName as $arrName => $arrValue) {
                $this->_vars[$arrName] = $arrValue;
            }
        } else {
            $this->_vars[$varName] = $varValue;
        }
    }

    /**
     * Build url with specified base
     *
     * @param string $params Url params
     * @return string
     */
    public function url($params)
    {
        //$url_base = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['SCRIPT_NAME'], Configure::read('App.baseUrl')) + 1);
        $url_base = (string)Configure::read('App.baseUrl', '/') . '/';

        return $url_base . substr($params, 1);
    }

    /**
     * Helper function for formatting numbers
     *
     * @param float $number Number to be formatted
     * @param int $places Number of decimal places
     * @return string
     */
    public function numFormat($number, $places = 2)
    {
        return number_format($number, $places, ',', '');
    }
}
