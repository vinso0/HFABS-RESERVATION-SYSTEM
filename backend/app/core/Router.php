<?php

class Router
{
    public function run()
    {
        $url = $_GET['url'] ?? 'auth/login';
        $url = explode('/', $url);

        $controllerName = ucfirst($url[0]) . 'Controller';
        $method = $url[1] ?? 'index';

        $controllerPath = __DIR__ . '/../controllers/' . $controllerName . '.php';
        if (file_exists($controllerPath)) {
            require_once $controllerPath;
        } else {
            die("Controller $controllerName not found.");
        }

        $controller = new $controllerName();
        if (method_exists($controller, $method)) {
            $controller->$method();
        } else {
            die("Method $method not found in controller $controllerName");
        }
    }
}