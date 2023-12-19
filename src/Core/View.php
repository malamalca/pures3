<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    public string $area = 'Pures';

    /**
     * @var array<string, mixed> $_vars
     */
    private $_vars = null;

    /**
     * @var array<string, mixed> $_options
     */
    private $_options = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $vars Passed variables
     * @param array<string, mixed> $options Passed options
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

        $templatePath = TEMPLATES . $this->area . DS . $controllerName . DS;

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
     * Element function
     *
     * @param string $element Element name
     * @return string|false
     */
    public function element($element)
    {
        $App = App::getInstance();

        $templatePath = TEMPLATES;
        $templateName = $element;
        $templateFile = realpath($templatePath . $templateName . '.php');

        if (
            empty($templateFile) ||
            strpos($templateFile, $templatePath) !== 0
        ) {
            die(sprintf('Template "%s" does not exist', $templateName . '.php'));
        }

        extract($this->_vars);

        ob_start();
        include $templateFile;
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }

    /**
     * Set variable for view render
     *
     * @param string|array<string, mixed> $varName Variable name or array with variables
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
     * Get variable from view render
     *
     * @param string $varName Variable name
     * @return mixed
     */
    public function get($varName)
    {
        if (isset($this->_vars[$varName])) {
            return $this->_vars[$varName];
        }

        return null;
    }

    /**
     * Build url with specified base
     *
     * @param string $params Url params
     * @return string
     */
    public function url($params)
    {
        return App::url($params);
    }

    /**
     * Helper function for formatting numbers
     *
     * @param float $number Number to be formatted
     * @param int $places Number of decimal places
     * @param string $decimalSeparator Decimal separator
     * @return string
     */
    public function numFormat($number, $places = 2, $decimalSeparator = ',')
    {
        return number_format($number, $places, $decimalSeparator, '');
    }
}
