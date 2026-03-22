<?php

class Router
{
    public function run()
    {
        $url = $_GET['url'] ?? 'auth/login';
        
        // Debug: Log the incoming URL
        error_log("Router received URL: " . $url);
        $url = explode('/', $url);

        // Handle API routes
        // First segment determines the controller
        $controllerName = ucfirst($url[0]) . 'Controller';
        $method = $url[1] ?? 'index';
        
        // Extract parameters from URL
        $params = array_slice($url, 2);

        $controllerPath = __DIR__ . '/../controllers/' . $controllerName . '.php';
        if (file_exists($controllerPath)) {
            require_once $controllerPath;
        } else {
            http_response_code(404);
            die("Controller $controllerName not found.");
        }

        $controller = new $controllerName();
        if (method_exists($controller, $method)) {
            // Call method with parameters
            call_user_func_array(array($controller, $method), $params);
        } else {
            // Check if we have an ID parameter and should call show() or services()
            if (is_numeric($method)) {
                // If next segment is 'services', call services() with branch ID
                if (isset($url[2]) && $url[2] === 'services') {
                    if (method_exists($controller, 'services')) {
                        call_user_func_array(array($controller, 'services'), array($method));
                    } else {
                        http_response_code(404);
                        die("Method services not found in controller $controllerName");
                    }
                } elseif (isset($url[2]) && $url[2] === 'categories') {
                    // If next segment is 'categories', call categories() with branch ID
                    if (method_exists($controller, 'categories')) {
                        call_user_func_array(array($controller, 'categories'), array($method));
                    } else {
                        http_response_code(404);
                        die("Method categories not found in controller $controllerName");
                    }
                } elseif (isset($url[2]) && $url[2] === 'reviews') {
                    // If next segment is 'reviews', call reviews() with branch ID
                    if (method_exists($controller, 'reviews')) {
                        call_user_func_array(array($controller, 'reviews'), array($method));
                    } else {
                        http_response_code(404);
                        die("Method reviews not found in controller $controllerName");
                    }
                } elseif (isset($url[2]) && $url[2] === 'reservations') {
                    // If next segment is 'reservations', call reservations() with user ID
                    if (method_exists($controller, 'reservations')) {
                        call_user_func_array(array($controller, 'reservations'), array($method));
                    } else {
                        http_response_code(404);
                        die("Method reservations not found in controller $controllerName");
                    }
                } else {
                    // Otherwise, call show() with ID
                    if (method_exists($controller, 'show')) {
                        call_user_func_array(array($controller, 'show'), array($method));
                    } else {
                        http_response_code(404);
                        die("Method show not found in controller $controllerName");
                    }
                }
            } else {
                http_response_code(404);
                die("Method $method not found in controller $controllerName");
            }
        }
    }
}