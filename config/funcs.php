<?php
/**
 * Helper debug funtion
 *
 * @return void
 */
function dd()
{
    if (\App\Core\Configure::read('debug')) {
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

/**
 * Return the first element in an array passing a given truth test.
 *
 * @param  iterable  $array
 * @param  callable|null  $callback
 * @param  mixed  $default
 * @return mixed
 */
function array_first($haystack, callable $callback = null, $default = null) {
    if (is_null($callback)) {
        if (empty($haystack)) {
            return $default;
        }

        foreach ($haystack as $item) {
            return $item;
        }
    }

    foreach ($haystack as $key => $value) {
        if ($callback($value, $key)) {
            return $value;
        }
    }

    return $default;
}