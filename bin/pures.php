#!/usr/bin/php -q
<?php
// Check platform requirements
require dirname(__DIR__) . '/config/bootstrap.php';

use App\Core\CommandRunner;

// Build the runner with an application and root executable name.
$runner = new CommandRunner();
exit($runner->run($argv));
