<?php
declare(strict_types=1);

namespace App\Core;

use Monolog\Logger;

class Log
{
    private static $instance = null;
    private static $logger = null;

    /**
     * Singleton constructor
     *
     * @return void
     */
    private function __construct()
    {
        // The expensive process (e.g.,db connection) goes here.
        self::$logger = new Logger('app');

        $handlers = Configure::read('Log');

        foreach ((array)$handlers as $handler) {
            $class = reset($handler);

            if (!empty($handler['formatter'])) {
                $formatterClass = reset($handler['formatter']);
                $formatterParams = array_values(array_slice($handler['formatter'], 1));
                $formatter = new $formatterClass(...$formatterParams);
                unset($handler['formatter']);
            }

            $params = array_values(array_slice($handler, 1));

            $handlerClass = new $class(...$params);
            if (isset($formatter)) {
                $handlerClass->setFormatter($formatter);
            }

            self::$logger->pushHandler($handlerClass);
        }
    }

    /**
     * Singleton instance getter
     *
     * @return \App\Core\Log
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Log();
        }

        return self::$instance;
    }

    /**
     * Get instance's logger
     *
     * @return \Monolog\Logger
     */
    public static function getLogger()
    {
        if (self::$instance == null) {
            self::$instance = new Log();
        }

        return self::$logger;
    }

    /**
     * Log debug
     *
     * @return void
     */
    public static function d()
    {
        call_user_func_array([self::getLogger(), 'debug'], func_get_args());
    }

    /**
     * Log info
     *
     * @return void
     */
    public static function info()
    {
        call_user_func_array([self::getLogger(), 'info'], func_get_args());
    }

    /**
     * Log warning
     *
     * @return void
     */
    public static function warn()
    {
        call_user_func_array([self::getLogger(), 'warning'], func_get_args());
    }

    /**
     * Log error
     *
     * @return void
     */
    public static function error()
    {
        call_user_func_array([self::getLogger(), 'error'], func_get_args());
    }

    /**
     * Log critical
     *
     * @return void
     */
    public static function critical()
    {
        call_user_func_array([self::getLogger(), 'critical'], func_get_args());
    }
}
