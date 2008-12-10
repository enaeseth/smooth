<?php

// SMOOTH: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

class SmoothView {
    private $path;
    
    public function __construct($path) {
        $this->path = $path;
    }
    
    public function getPath() {
        return $this->path;
    }
    
    public function render(&$context) {
        if (is_array($context)) {
            extract($context, EXTR_OVERWRITE | EXTR_REFS);
        } else if (is_object($context)) {
            foreach ($context as $k => $v) {
                $$k = $v;
            }
        } else {
            throw new InvalidArgumentException('Rendering context must be '.
                'an array or an object.');
        }
        
        include $this->path;
    }
}
