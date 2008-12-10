<?php

// SMOOTH: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

class SmoothException extends RuntimeException {
    
}

class SmoothSetupException extends SmoothException {
    
}

class SmoothExecutionException extends SmoothException {
    
}

abstract class SmoothControllerException extends SmoothExecutionException {
    public $controller;
    public $class;
    public $path;
    
    public function __construct($controller, $class, $path) {
        parent::__construct($this->createMessage($controller, $class, $path));
        
        $this->controller = $controller;
        $this->class = $class;
        $this->path = $path;
    }
    
    protected abstract function createMessage($controller, $class, $path);
}

class SmoothControllerFileMissingException extends SmoothControllerException {
    protected function createMessage($controller, $class, $path) {
        return "The controller '$controller' ($class) could not be loaded; ".
            "the file '$path' does not exist.";
    }
}

class SmoothControllerClassMissingException extends SmoothControllerException {
    protected function createMessage($controller, $class, $path) {
        return "The controller '$controller' could not be loaded; its class ".
            "$class was not defined by file '$path'.";
    }
}
