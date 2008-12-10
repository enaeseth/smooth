<?php

// SMOOTH: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

smooth_load('view', 'application', 'request', 'response');

class SmoothController {
    private $application;
    private $name;
    private $request;
    private $response;
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
}
