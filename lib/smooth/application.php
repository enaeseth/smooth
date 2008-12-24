<?php

// Smooth: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

smooth_load('configuration', 'errors', 'routing', 'controller', 'request',
    'response');

class SmoothApplication {
    public $root;
    public $config;
    public $router;
    public $environment;
    
    public function __construct($root=null, $runtime_config=null) {
        $this->config = new SmoothConfiguration();
        
        if (!$root) {
            // Automatically determine the application root.
            $root = dirname($_SERVER['SCRIPT_FILENAME']);
        }
        $this->root = $root;
        
        $env = ($runtime_config && $runtime_config['environment'])
            ? $runtime_config['environment']
            : 'development';
        $this->loadConfiguration($env);
        if ($runtime_config)
            $this->config->merge($runtime_config);
            
        $this->environment = $env;
        $router = $this->config->get('smooth/router', 'default');
        $this->router = SmoothRouter::get($router, $this);
    }
    
    public function run() {
        $request = new SmoothRequest();
        $response = new SmoothResponse($request);
        
        try {
            if ($this->isDevelopmentEnvironment()) {
                ob_start();
                $this->invoke($request, $response);
                ob_end_flush();
            } else {
                $this->invoke($request, $response);
            }
        } catch (SmoothHTTPError $error) {
            $this->handleError($request, $error);
        } catch (Exception $exception) {
            $this->handleException($request, $exception);
        }
    }
    
    public function isDevelopmentEnvironment() {
        return $this->environment == 'development';
    }
    
    public function isTestingEnvironment() {
        return $this->environment == 'testing';
    }
    
    public function isProductionEnvironment() {
        return $this->environment == 'development';
    }
    
    public function completeURL(SmoothRequest $req, $path) {
        $protocol = ($req->secure) ? 'https' : 'http';
        $host = $req->host;
        $port = $req->server_port;
        $port = ($req->secure && $port == 443 || !$req->secure && $port == 80)
            ? ''
            : ":$port";
        
        return "$protocol://{$host}{$port}{$req->script_name}{$path}";
    }
    
    private function invoke(SmoothRequest $request, SmoothResponse $response) {
        $route = $this->router->route($request);
        if (!$route) {
            throw new SmoothHTTPError('The requested URL "'.
                $request->request_uri.'" was not found on this server.', 404);
        }
        
        $controller = $route['controller'];
        $controller_class = $this->loadController($controller);
        try {
           $reflector = new ReflectionClass($controller_class); 
        } catch (ReflectionException $e) {
            throw new SmoothControllerClassMissingException($controller,
                $controller_class, $this->getControllerPath($controller));
        }
        $controller = new $controller_class($controller, $this,
            $request, $response);
        if (!($controller instanceof SmoothController)) {
            throw new SmoothInvalidControllerException('Class does not '.
                'inherit from SmoothController.', $route['controller'],
                $controller_class, $this->getControllerPath($controller));
        }
        
        $action = $route['action'];
        try {
            $method = $reflector->getMethod($action);
        } catch (ReflectionException $e) {
            throw new SmoothExecutionException(
                "Controller '$route[controller]' ($controller_class) does not ".
                "implement action '$action'."
            );
        }
        
        $args = array();
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            if (!$route[$name]) {
                if (!$param->isOptional()) {
                    throw new SmoothExecutionException(
                        "$controller_class::$action requires ".
                        "parameter '$name'."
                    );
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
    
    private function handleError($request, $error) {
        $code = $error->getCode();
        if ($code >= 301 && $code < 400) {
            // Redirection.
            return;
        }
        
        $page_title = $subtitle = "HTTP Error $code";
        $title = $error->getMessage();
        
        @header('Content-Type: text/html; charset=utf-8');
        include smooth_path('templates', 'error.php');
    }
    
    private function handleException($request, $exception) {
        $page_title = $title = get_class($exception).' at '.
            $request->request_uri;
        $subtitle = $exception->getMessage();
        
        @header('Content-Type: text/html; charset=utf-8');
        include smooth_path('templates', 'error.php');
    }
    
    protected function getControllerPath($name) {
        return path_join($this->root, 'controllers', "$name.php");
    }
    
    private function loadController($name) {
        $class = $this->config->get("smooth/controllers/$name");
        if (!$class) {
            $class = implode('', array_map('ucfirst', explode('_', $name))).
                'Controller';
        }
        
        if (!class_exists($class)) {
            $path = $this->getControllerPath($name);
            if (!file_exists($path)) {
                throw new SmoothControllerFileMissingException($name, $class,
                    $path);
            }
            require_once $path;
        }
        
        return $class;
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
    
    private function loadConfiguration($environment) {
        $exts = '{yml,yaml,conf}';
        
        foreach (array('{app,application}', $environment) as $source) {
            $file = "$source.$exts";
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
}

