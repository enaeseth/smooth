<?php

// SMOOTH: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

class SmoothConfiguration
{
    public function __construct($data=null) {
        if ($data)
            $this->merge($data);
    }
    
    public function get($field, $default=null) {
        $parts = explode('/', $field, 2);
        if (count($parts) == 1) {
            return (isset($this->$field) ? $this->$field : $default);
        }
        
        $field = $parts[0];
        $defer = $parts[1];
        if (!isset($this->$field) || !is_object($this->$field))
            return $default;
        
        return $this->$field->get($defer, $default);
    }
    
    public function merge($data) {
        if (!is_object($data) && !is_array($data)) {
            throw new InvalidArgumentException("Can only merge arrays and ".
                "objects with SmoothConfiguration objects.");
        }
        
        foreach ((array) $data as $k => $v) {
            if ($this->convertable($v)) {
                if (!$this->$k)
                    $this->$k = new SmoothConfiguration();
                $this->$k->merge($v);
            } else {
                $this->$k = $v;
            }
        }
    }
    
    private function convertable($v) {
        return (is_array($v) && $this->isAssociative($v)) ||
            (is_object($v) && !($v instanceof SmoothConfiguration));
    }
    
    private function isAssociative(array $data) {
        foreach (array_keys($data) as $k) {
            if (!is_numeric($k))
                return true;
        }
        
        return false;
    }
}
