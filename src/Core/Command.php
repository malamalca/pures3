<?php
declare(strict_types=1);

namespace App\Core;

class Command
{
    /**
     * Run command
     *
     * @return void
     */
    public function run()
    {
        $logger = Log::getLogger();
        $logger->pushProcessor(function ($entry) {
            $msg = $entry['message'];
            switch ($entry['level_name']) {
                case 'ERROR':
                    $entry['message'] = "\033[31m$msg \033[0m\n";
                    break;
                case 'WARNING': //warning
                    $entry['message'] = "\033[33m$msg \033[0m\n";
                    break;
            }

            return $entry;
        });
    }

    /**
     * Print string to console
     *
     * @param string $str Output string
     * @param string $type Output type
     * @return int
     */
    public function out($str, $type = 'info')
    {
        $ret = 0;
        switch ($type) {
            case 'error': //error
                echo "\033[31m$str \033[0m\n";
                $ret = -1;
                break;
            case 'success': //success
                echo "\033[32m$str \033[0m\n";
                $ret = 1;
                break;
            case 'warning': //warning
                echo "\033[33m$str \033[0m\n";
                break;
            case 'info': //info
                echo "\033[36m$str \033[0m\n";
                break;
        }

        return $ret;
    }
}
