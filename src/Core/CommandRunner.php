<?php
declare(strict_types=1);

namespace App\Core;

class CommandRunner
{
    /**
     * Default run routine
     *
     * @param array $args List of arguments
     * @param string $area Area of command to execute
     * @return void
     */
    public function run($args, $area = 'Pures')
    {
        if (empty($args[1])) {
            die('Specify Command!');
        }

        $className = '\\App\\Command\\' . $area . '\\' . $args[1];

        $commandClass = new $className();

        $func = [$commandClass, 'run'];
        if (is_callable($func)) {
            call_user_func_array($func, array_slice($args, 2));
        } else {
            throw new \Exception('Invalid Command.');
        }
    }
}
