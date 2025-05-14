<?php
require dirname(__DIR__) . '/config/bootstrap.php';

use App\Core\App;
use App\Core\Configure;

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->addRoute(['GET', 'POST'], '/pures/{controller}[/{action}[/{param1}[/{param2}[/{param3}]]]]', 'Pures');
    $r->addRoute(['GET', 'POST'], '/hrup/{controller}[/{action}[/{param1}[/{param2}]]]', 'Hrup');
    $r->addRoute(['GET'], '/project-image/{area}/{projectId}/{image}', 'ProjectImage');
    $r->addRoute(['GET'], '/project-image/{area}/{image}', 'ProjectImage');
    $r->addRoute(['GET'], '/', 'Index');
});

// Fetch method and URI from somewhere;
$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
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

if (file_exists(WWW_ROOT . $uri) && is_file(WWW_ROOT . $uri)) {
    $fullPath = realpath(WWW_ROOT . $uri);
    $finfo = finfo_open(FILEINFO_MIME_TYPE); // Return MIME type a la the 'mimetype' extension
    if ($finfo && $fullPath) {
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = finfo_file($finfo, $fullPath);
        finfo_close($finfo);

        switch($extension){
            case 'css':
                $mime = 'text/css';
                break;
            case 'js':
                $mime = 'application/javascript';
                break;
        }

        header('Content-Type: ' . $mime);
        readfile($fullPath);
        die;
    }
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
        switch ($handler) {
            case 'ProjectImage':
                $controllerName = 'App';
                $vars['action'] = 'projectImage';

                // za primere, ko ni podan projectId
                $vars = [
                    'area' => $vars['area'],
                    'projectId' => $vars['projectId'] ?? '',
                    'image' => $vars['image'],
                    'action' => 'projectImage'
                ];

                $handler = $vars['area'];
                break;
            case 'Index':
                $vars['controller'] = 'Projekti';
                $controllerName = $vars['controller'];
                $handler = 'Pures';
                break;
            default:
                $controllerName = $vars['controller'];
                $controllerName = str_replace('-', '', ucwords($controllerName, '-'));

                unset($vars['controller']);
        }

        App::dispatch($controllerName, $vars, $handler);

        break;
}
