<?php

// SMOOTH: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

class SmoothException extends RuntimeException {
    
}

class SmoothSetupException extends SmoothException {
    
}

class SmoothExecutionException extends SmoothException {
    
}

class SmoothControllerMissingException extends SmoothExecutionException {
    public $controller;
    public $class;
    public $path;
    
    public function __construct($controller, $class, $path) {
        parent::__construct("The controller $controller ($class) could not be ".
            " loaded; the file $path does not exist.");
        
        $this->controller = $controller;
        $this->class = $class;
        $this->path = $path;
    }
}
