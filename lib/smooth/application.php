<?php

// SMOOTH: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

smooth_load('configuration', 'errors', 'routing', 'controller', 'request',
    'response');

class SmoothApplication {
    public $root;
    public $config;
    public $router;
    
    public function __construct($root=null, $runtime_config=null) {
        $this->config = new SmoothConfiguration();
        
        if (!$root) {
            // Automatically determine the application root.
            $root = dirname($_SERVER['SCRIPT_FILENAME']);
        }
        $this->root = $root;
        
        $this->loadConfiguration();
        if ($runtime_config)
            $this->config->merge($runtime_config);
            
        $router = $this->config->get('smooth/router', 'default');
        $this->router = SmoothRouter::get($router, $this);
    }
    
    public function run() {
        $request = new SmoothRequest();
        $response = new SmoothResponse($request);
        
        $route = $this->router->route($request);
        if (!$route) {
            $route = $this->getErrorHandler(404);
            if (!$route) {
                $this->respondToError(404);
                return false;
            }
        }
        
        $controller_class = $this->loadController($route['controller']);
        $reflector = new ReflectionClass($controller_class);
        $controller = new $controller_class($route['controller'], $this,
            $request, $response);
        
        $action = $controller['action'];
        $method = $reflector->getMethod($action);
        if (!$method) {
            throw new SmoothException("Controller $route[controller] ".
                "($controller_class) does not implement $action.");
        }
        
        $args = array();
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            if (!$route[$name]) {
                if (!$param->isOptional()) {
                    throw new SmoothException("$controller_class::$action ".
                        "requires parameter '$name'.");
                }
                $args[] = ($param->isDefaultValueAvailable())
                    ? $param->getDefaultValue()
                    : null;
            } else {
                $args[] = $route[$name];
            }
        }
        
        $result = $method->invokeArgs($controller, $args);
        if (!$controller->rendered()) {
            if (is_array($result) || is_object($result)) {
                $controller->render($action, $result);
            } else {
                $controller->render($action);
            }
        }
    }
    
    private function loadController($name) {
        $class_name = $this->config->get("smooth/controllers/$name");
        if (!$class_name) {
            $class_name = implode('', array_map('ucfirst',
                explode('_', $name))).'Controller';
        }
        
        if (!class_exists($class_name)) {
            $path = path_join($this->root, 'controllers', "$name.php");
            if (!file_exists($path)) {
                throw new SmoothControllerMissingException($name, $class_name,
                    $path);
            }
            require_once $path;
        }
        
        return $class_name;
    }
    
    private function getErrorHandler($error) {
        $handlers = $this->config->get('smooth/error_handlers');
        if (!$handlers || !isset($handlers[$error]))
            return null;
        
        $handler = $handlers[$error];
        if (is_string($handler)) {
            list($controller, $action) = explode('/', $handler, 2);
            return array('controller' => $controller, 'action' => $action);
        }
        return $handler;
    }
    
    private function respondToError($code) {
        $desc = SmoothResponse::getStatusDescription($code);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="en" xml:lang="en">
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <title>Error: <?= $desc ?></title>
</head>
<body>
    <h1><?= $desc ?></h1>
</body>
</html>
<?
    }
    
    private function loadConfiguration() {
        $file = '{app,application}.{yml,yaml,conf}';
        $pattern = path_join($this->root, '{config,configuration}', $file);
        
        $files = glob($pattern, GLOB_BRACE);
        if (!$files) {
            $files = glob(path_join($this->root, $file), GLOB_BRACE);
        }
        
        if (!$files)
            return;
        
        if (!is_readable($files[0])) {
            throw new SmoothSetupException('Configuration file "'.
                $files[0].'" is not readable.');
        }
        $this->config->merge(yaml_load($files[0]));
    }
}

