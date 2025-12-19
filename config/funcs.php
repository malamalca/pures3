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
 * Array sum values
 *
 * @param array &$a Destination array
 * @param array $b Array of elements to be sumed to $a
 * @return array
 */
function array_sum_values(&$a, $b) {
    $ret = $a;
    foreach ($b as $k => $v) {
        $ret[$k] = ($ret[$k] ?? 0) + $v;
    }

    return $ret;
}

/**
 * Array subtract values
 *
 * @param array &$a Destination array
 * @param array $b Array of elements to be subtracted from $a
 * @return array
 */
function array_subtract_values(&$a, $b) {
    $ret = $a;
    foreach ($b as $k => $v) {
        $ret[$k] = ($ret[$k] ?? 0) - $v;
    }

    return $ret;
}

/**
 * Return the first element in an array passing a given truth test.
 *
 * @param  iterable  $array
 * @param  callable  $callback
 * @param  mixed  $default
 * @return mixed
 */
function array_first_callback($haystack, ?callable $callback, $default = null) {
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

/**
 * Določi najbližjo vrednost
 *
 * @param array $haystack Zaloga vrednosti
 * @param float $num Vrednost
 * @return mixed
 */
function array_nearest($haystack, $num)
{
    $closest = null;
    foreach ($haystack as $item) {
        if ($closest === null || abs($num - $closest) > abs($item - $num)) {
            $closest = $item;
        }
    }

    return $closest;
}