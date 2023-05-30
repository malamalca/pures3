<?php
declare(strict_types=1);

namespace App\Core;

class CommandRunner
{
    /**
     * Default run routine
     *
     * @param array $args List of arguments
     * @return void
     */
    public function run($args)
    {
        if (empty($args[1])) {
            die('Specify Command!');
        }

        $className = '\\App\\Command\\' . $args[1];

        $commandClass = new $className();

        $func = [$commandClass, 'run'];
        if (is_callable($func)) {
            call_user_func_array($func, array_slice($args, 2));
        } else {
            throw new \Exception('Invalid Command.');
        }
    }
}
