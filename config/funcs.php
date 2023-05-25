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

/**
 * Helper print array
 *
 * @param array $a Array data
 * @return string
 */
function ar($a) {
    return '|' . implode(' | ', array_map(fn($v) => str_pad(number_format(round($v, 2), 2, '.', ''), 8, ' ', STR_PAD_LEFT), $a)) . '|' . PHP_EOL;
}