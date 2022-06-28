<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once realpath(__DIR__."/../vendor/autoload.php");

require '../const.php';

$router = new \Klein\Klein();
$request = \Ady\MVP2\RestRequest::createFromGlobals();

$router->respond('*',function (
    \Klein\Request $request,\Klein\Response $response,\Klein\ServiceProvider $service) {
    $hasAccept = isset($request->headers()['Accept']);
    if(!$hasAccept){
        http_response_code(415);
        die;
    } else {
        $h = $request->headers()['Accept'];

        if(strpos($h, "application/json") === false){
            http_response_code(415);
            die;
        }
    }

});

$routing = require_once "../src/routing.php";

foreach ($routing as $controller=> $routes){
    $router->with('/'.$controller, function ($klein) use ($router, $routing,$routes,$controller) {

        foreach ($routes as $route){
            $method = $route[0];
            $uri = $route[1];
            $controllerMethod = $route[2];

            $router->respond( $method, $uri, function($req, $res,$serv, $app)
                use ($controller, $route, $uri,$controllerMethod){
                $controllerName = '\\Ady\\MVP2\\controllers\\'.ucfirst($controller).'Controller';
                $controllerInstance = new $controllerName($req, $res, $serv, $app, $route[2]);
                $controllerInstance->run($route[2]);
            });
        }
    });
}

$router->onHttpError(function ($code, $router) {

    if ($code >= 400 && $code < 500) {

        $router->response()->body(
            'Oh no, a bad error happened that caused a '. $code
        );
    } elseif ($code >= 500 && $code <= 599) {
        error_log('uhhh, something bad happened');
    }
});

$router->dispatch($request);

