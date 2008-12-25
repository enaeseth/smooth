<?php

// Smooth: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

smooth_load('view', 'application', 'request', 'response');

class SmoothController {
    public $application;
    public $name;
    protected $request;
    protected $response;
    private $rendered;
    
    public function __construct($name, SmoothApplication $app,
        SmoothRequest $req, SmoothResponse $response) 
    {
        $this->application = $app;
        $this->name = $name;
        $this->request = $req;
        $this->response = $response;
        $this->rendered = false;
    }
    
    public function rendered() {
        return $this->rendered;
    }
    
    public function render($name, $context=null) {
        $parts = explode('/', $name, 2);
        list($controller, $view) = (count($parts) == 1)
            ? array($this->name, $name)
            : $parts;
        
        $path = path_join($this->application->root, 'views', $controller,
            "$view.php");
        if (!file_exists($path))
            return false;
        
        $view = new SmoothView($path);
        if (!$context)
            $context = array();
        if (is_array($context)) {
            $context['controller'] = $this;
            $context['application'] = $this->application;
        }
        $view->render($context);
        $this->rendered = true;
    }
    
    public function url($route_name, $vars=null) {
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
        }
        
        $path = $this->application->router->getPath($route_name, $vars);
        return $this->application->completeURL($this->request, $path);
    }
    
    public function redirect($url, $vars=null) {
        if (substr($url, 0, 6) == 'route:') {
            if (!is_array($vars)) {
                $vars = func_get_args();
                array_shift($vars);
            }
            
            $url = $this->url(substr($url, 6), $vars);
        }
        
        $this->response->header('Location', $url);
        throw new SmoothHTTPError(302);
    }
}
