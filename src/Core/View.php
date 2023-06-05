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
     * @var array $_options
     */
    private $_options = null;

    /**
     * Constructor
     *
     * @param array $vars Passed variables
     * @param array $options Passed options
     */
    public function __construct(?array $vars = null, ?array $options = null)
    {
        $this->_vars = $vars;
        $this->_options = $options;
    }

    /**
     * Render function
     *
     * @param string $controllerName Controller name
     * @param string $methodName Method name
     * @return string|false
     */
    public function render($controllerName, $methodName)
    {
        $App = App::getInstance();

        $templatePath = TEMPLATES . $controllerName . DS;

        $templateName = $methodName;
        if (isset($this->_options['template'])) {
            $templateFile = $this->_options['template'];
        }
        $templateFile = realpath($templatePath . $templateName . '.php');

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
        $layoutName = 'default';
        if (isset($this->_options['layout'])) {
            $layoutName = $this->_options['layout'];
        }
        $layoutFile = realpath(TEMPLATES . 'layouts' . DS . $layoutName . '.php');

        ob_start();
        require $layoutFile;
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
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
