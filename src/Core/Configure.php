<?php
declare(strict_types=1);

namespace App\Core;

class Configure
{
    /**
     * @var \App\Core\Configure|null
     */
    private static $_instance = null;

    /**
     * @var array
     */
    private $config;

    /**
     * Config constructor.
     *
     * @param array $config Config array
     * @return void
     */
    private function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Returns the instance.
     *
     * @param array $config Config array
     * @return \App\Core\Configure
     */
    public static function getInstance($config = null)
    {
        if (self::$_instance == null) {
            self::$_instance = new self($config);
        }

        return self::$_instance;
    }

    /**
     * Read a config item.
     *
     * @param string $key Configure key
     * @param mixed $default Default value
     * @return mixed
     */
    public static function read($key, $default = null)
    {
        $instance = self::getInstance();

        $ret = $default;
        $levels = (array)explode('.', $key);

        $base = $instance->config;

        $i = 0;

        while (isset($base[$levels[$i]])) {
            if (is_array($base[$levels[$i]]) && ($i < count($levels) - 1)) {
                $base = $base[$levels[$i]];

                $i++;
            } else {
                if ($i == count($levels) - 1) {
                    return $base[$levels[$i]];
                }
            }
        }

        return $ret;
    }

    /**
     * Store configuration options to class.
     *
     * @param string $key Configure key
     * @param mixed $values Configuration values
     * @return void
     */
    public static function store($key, $values)
    {
        $instance = self::getInstance();

        $instance->config[$key] = $values;
    }

    /**
     * Write configuration option to class.
     *
     * @param string $key Configure key
     * @param mixed $value Configuration value
     * @return void
     */
    public static function write($key, $value)
    {
        $instance = self::getInstance();

        $levels = (array)explode('.', $key);

        $base = &$instance->config;

        $i = 0;

        while (isset($base[$levels[$i]])) {
            if (is_array($base[$levels[$i]]) && ($i < count($levels) - 1)) {
                $base = &$base[$levels[$i]];

                $i++;
            } else {
                $base[$levels[$i]] = $value;
                break;
            }
        }
    }

    /**
     * Empty function
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Empty function
     *
     * @return void
     */
    public function __wakeup()
    {
    }

    /**
     * Empty function
     *
     * @return void
     */
    public function __destruct()
    {
        self::$_instance = null;
    }
}
