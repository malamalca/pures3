<?php
/**
 * Use the DS to separate the directories in other defines
 */
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * The full path to the directory which holds "App", WITHOUT a trailing DS.
 */
define('ROOT', dirname(__DIR__));

/**
 * The actual directory name for the "App".
 */
define('APP_DIR', 'src');

/**
 * Path to the application's directory.
 */
define('APP', ROOT . DS . APP_DIR . DS);

/**
 * Path to the config directory.
 */
define('CONFIG', ROOT . DS . 'config' . DS);

/**
 * Path to the logs directory.
 */
// A caller may give every run its own directories (PHPURES_LOGS,
// PHPURES_TMP): absolute paths of existing directories.
$runDir = function (string $name, string $default): string {
    $dir = getenv($name);
    if (is_string($dir) && $dir !== '' && is_dir($dir)) {
        return rtrim($dir, '\\/') . DS;
    }

    return $default;
};
define('LOGS', $runDir('PHPURES_LOGS', ROOT . DS . 'logs' . DS));

/**
 * File path to the webroot directory.
 */
define('WWW_ROOT', ROOT . DS . 'webroot' . DS);

/**
 * Path to the templates directory.
 */
define('TEMPLATES', ROOT . DS . 'templates' . DS);

/**
 * Path to the temporary files directory.
 */
define('TMP', $runDir('PHPURES_TMP', ROOT . DS . 'tmp' . DS));

/**
 * Path to the projects directory.
 */
define('PROJECTS', ROOT . DS . 'projects' . DS);

/**
 * Path to the schemas directory.
 */
define('SCHEMAS', ROOT . DS . 'schemas' . DS);

/**
 * Path to the resources directory.
 */
define('RESOURCES', ROOT . DS . 'resources' . DS);
