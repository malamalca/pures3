<?php
declare(strict_types=1);

/**
 * Configure paths required to find CakePHP + general filepath
 * constants
 */
require dirname(__DIR__) . '/config/paths.php';
require dirname(__DIR__) . '/config/funcs.php';
require dirname(__DIR__) . '/config/lookups.php';

// Use composer to load the autoloader.
require ROOT . DS . 'vendor' . DS . 'autoload.php';

use App\Core\Configure;

$defaultConfig = require dirname(__DIR__) . DS . '/config/app_default.php';

$config = [];
$appConfigFile = dirname(__FILE__) . DS . 'app.php';
if (file_exists($appConfigFile)) {
    $config = require $appConfigFile;
}
$config = array_replace_recursive($defaultConfig, $config);

$config['lookups'] = $lookups;
Configure::getInstance($config);

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
