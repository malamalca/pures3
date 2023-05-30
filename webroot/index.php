<?php
require dirname(__DIR__) . '/config/bootstrap.php';

use App\Core\App;
use App\Core\Configure;

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->addRoute(['GET', 'POST'], '/{controller}[/{action}[/{param1}[/{param2}]]]', 'htaccess');
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
if (Configure::read('App.baseUrl') == '') {
    $uri = $_SERVER['REQUEST_URI'];
} else {
    $uri = substr(
        $_SERVER['REQUEST_URI'], 
        strpos($_SERVER['REQUEST_URI'], Configure::read('App.baseUrl')) + strlen(Configure::read('App.baseUrl'))
    );
}

// Add slash
if (substr($uri, 0, 1) !== '/') {
    $uri = '/' . $uri;
}

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        header("HTTP/1.0 404 Not Found");
        echo "Route Not Found.\n";
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        header("HTTP/1.0 405 Method Not Allowed");
        echo "Route Not Allowed.\n";
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        $controllerName = $vars['controller'];
        
        // convert to CamelCase
        $controllerName = str_replace('-', '', ucwords($controllerName, '-'));

        unset($vars['controller']);

        App::dispatch($controllerName, $vars);

        break;
}
