<?php

// Smooth: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

class SmoothException extends RuntimeException {
    
}

class SmoothSetupException extends SmoothException {
    
}

class SmoothExecutionException extends SmoothException {
    
}

class SmoothHTTPError extends SmoothExecutionException {
    private $description;
    
    public function __construct($message, $code=null) {
        if (func_num_args() == 1) {
            if (is_numeric($message)) {
                $code = $message;
                $message = null;
            } else {
                $code = 500;
            }
        }
        parent::__construct($message, $code);
        $this->description = SmoothResponse::getStatusDescription($code);
    }
    
    public function getDescription() {
        return $this->description;
    }
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

class SmoothInvalidControllerException extends SmoothControllerException {
    private $reason;
    
    public function __construct($reason, $controller, $class, $path) {
        $this->reason = $reason;
        parent::__construct($controller, $class, $path);
    }
    
    protected function createMessage($controller, $class, $path) {
        return "Invalid controller '$controller' ($class): {$this->reason}.";
    }
}

class SmoothMiddlewareException extends SmoothExecutionException {
    public $name;
    public $class;
    
    function __construct($message, $name, $class) {
        parent::__construct($message);
        $this->name = $name;
        $this->class = $class;
    }
}
