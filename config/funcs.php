<?php
/**
 * Helper debug funtion
 *
 * @return void
 */
function dd()
{
    if (Configure::read('debug')) {
        echo '<pre>';
        var_dump(func_get_args());
        echo '</pre>';
        die;
    }
}

/**
 * Helper htmlentities() funtion
 *
 * @param string $output Output data
 * @return void
 */
function h($output)
{
    return htmlentities($output, ENT_COMPAT, 'UTF-8');
}