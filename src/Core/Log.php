<?php
declare(strict_types=1);

namespace App\Core;

use Monolog\Logger;

class Log
{
    private static ?\App\Core\Log $instance = null;
    private static ?\Monolog\Logger $logger = null;

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

            $addCliProcessor = !empty($handler['cli']);
            unset($handler['cli']);

            if (!empty($handler['formatter'])) {
                $formatterClass = reset($handler['formatter']);
                $formatterParams = array_values(array_slice($handler['formatter'], 1));
                $formatter = new $formatterClass(...$formatterParams);
                if (is_a($formatter, '\Monolog\Formatter\LineFormatter')) {
                    $formatter->includeStacktraces(true);
                }
                unset($handler['formatter']);
            }

            $params = array_values(array_slice($handler, 1));

            /** @var \Monolog\Handler\HandlerInterface $handlerClass */
            $handlerClass = new $class(...$params);
            if (isset($formatter) && is_callable([$handlerClass, 'setFormatter'])) {
                $handlerClass->setFormatter($formatter);
            }

            if ($addCliProcessor && is_a($handlerClass, '\Monolog\Handler\StreamHandler')) {
                $handlerClass->pushProcessor(function ($entry) {
                    $msg = $entry['message'];
                    switch ($entry['level_name']) {
                        case 'ERROR':
                            $entry['message'] = "\033[31m$msg \033[0m";
                            break;
                        case 'WARNING':
                            $entry['message'] = "\033[33m$msg \033[0m";
                            break;
                        case 'INFO':
                            $entry['message'] = "\033[36m$msg \033[0m";
                            break;
                    }

                    return $entry;
                });
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
     * @return null|\Monolog\Logger
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
    public static function debug()
    {
        $func = [self::getLogger(), 'debug'];
        if (is_callable($func)) {
            call_user_func_array($func, func_get_args());
        }
    }

    /**
     * Log info
     *
     * @return void
     */
    public static function info()
    {
        $func = [self::getLogger(), 'info'];
        if (is_callable($func)) {
            call_user_func_array($func, func_get_args());
        }
    }

    /**
     * Log warning
     *
     * @return void
     */
    public static function warn()
    {
        $func = [self::getLogger(), 'warning'];
        if (is_callable($func)) {
            call_user_func_array($func, func_get_args());
        }
    }

    /**
     * Log error
     *
     * @return void
     */
    public static function error()
    {
        $func = [self::getLogger(), 'error'];
        if (is_callable($func)) {
            call_user_func_array($func, func_get_args());
        }
    }

    /**
     * Log critical
     *
     * @return void
     */
    public static function critical()
    {
        $func = [self::getLogger(), 'critical'];
        if (is_callable($func)) {
            call_user_func_array($func, func_get_args());
        }
    }
}
