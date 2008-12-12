<?php

// Smooth: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

class SmoothView {
    private $path;
    
    private static $context_tree = array();
    
    public function __construct($path) {
        $this->path = $path;
    }
    
    public function getPath() {
        return $this->path;
    }
    
    public function render(&$context) {
        foreach (self::$context_tree as &$older_context) {
            if (is_array($older_context)) {
                extract($older_context, EXTR_OVERWRITE | EXTR_REFS);
            } else {
                foreach ($older_context as $k => $v) {
                    $$k = &$older_context->$k;
                }
            }
            
        }
        
        if (is_array($context)) {
            extract($context, EXTR_OVERWRITE | EXTR_REFS);
        } else if (is_object($context)) {
            foreach ($context as $k => $v) {
                $$k = &$context->$k;
            }
        } else {
            throw new InvalidArgumentException('Rendering context must be '.
                'an array or an object.');
        }
        
        self::$context_tree[] =& $context;
        include $this->path;
        array_pop(self::$context_tree);
    }
}
