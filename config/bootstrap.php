<?php
/**
 * Configure paths required to find CakePHP + general filepath
 * constants
 */
require __DIR__ . '/paths.php';
require __DIR__ . '/funcs.php';
require __DIR__ . '/lookups.php';

// Use composer to load the autoloader.
require ROOT . DS . 'vendor' . DS . 'autoload.php';

use App\Core\Configure;
use App\Core\Log;
use Monolog\ErrorHandler;

$defaultConfig = require(dirname(__FILE__) . DS . 'app_default.php');

$config = [];
$appConfigFile = dirname(__FILE__) . DS . 'app.php';
if (file_exists($appConfigFile)) {
    $config = require($appConfigFile);
}
$config = array_replace_recursive($defaultConfig, $config);

$config['lookups'] = $lookups;
Configure::getInstance($config);

// Display exceptions
function echo_exception_handler($e) {
    echo sprintf('Uncaught Exception %s: "%s" at %s line %s', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine());
}

if (Configure::read('debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', true);
    set_exception_handler('echo_exception_handler');
}


// Handle php error logs with monolog
$logger = Log::getInstance()->getLogger();
ErrorHandler::register($logger);

/**
 * Set server timezone to UTC. You can change it to another timezone of your
 * choice but using UTC makes time calculations / conversions easier.
 */
date_default_timezone_set('UTC');

/**
 * Configure the mbstring extension to use the correct encoding.
 */
//mb_internal_encoding($config['App']['encoding']);

/**
 * Set the default locale. This controls how dates, number and currency is
 * formatted and sets the default language to use for translations.
 */
ini_set('intl.default_locale', 'sl_SI');

/*
 * Include the CLI bootstrap overrides.
 */
if (PHP_SAPI === 'cli') {
    require CONFIG . 'bootstrap_cli.php';
}


session_start();