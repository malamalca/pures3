#!/usr/bin/php -q
<?php
// Check platform requirements
require dirname(__DIR__) . '/config/bootstrap.php';

use App\Core\CommandRunner;

// Build the runner with an application and root executable name.
$runner = new CommandRunner();
$runner->run($argv, 'Hrup');
// A rejected input must not look like a success (exit code 0).
exit(\App\Core\Command::exitCode());
