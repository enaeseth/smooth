<?php

// Smooth: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

class SmoothPatternRouter extends SmoothRouter {
    private $table = array();
    
    public function __construct(SmoothApplication $application) {
        parent::__construct($application);
        
        $this->loadRoutes();
    }
    
    public function route(SmoothRequest $request) {
        $length = count($this->table);
        for ($i = 0; $i < $length; $i++) {
            $match = $this->table[$i]->match($request->method,
                $request->path_info);
            if (!$match)
                continue;
            
            return $match;
        }
        
        return false;
    }
    
    private function loadRoutes() {
        $file = $this->getRouteFile(array('conf', 'txt'));
        if (!$file) {
            throw new SmoothSetupException('No routes file exists.');
        }
        
        $reader = new SmoothPatternFileReader($file);
        foreach ($reader as $entry) {
            $path = $entry['path'];
            $matches = array();
            preg_match_all('#(\(.+?\))?:(\w+)#', $path, $matches,
                PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
                
            $pattern = '#^';
            $last = 0;
            $groups = array();
            foreach ($matches as $m) {
                $pattern .= preg_quote(substr($path, $last, $m[0][1] - $last),
                    '#');
                $group = array('name' => $m[2][0]);
                if ($m[1][0]) {
                    $group['pattern'] = '/^'.$m[1][0].'$/';
                }
                $groups[] = $group;
                $pattern .= '(.+?)';
                $last = $m[0][1] + strlen($m[0][0]);
            }
            
            $pattern .= preg_quote(substr($path, $last), '#');
            if (substr($pattern, -1) != '/')
                $pattern .= '/?';
            $pattern .= '$#';
            
            $this->table[] = new SmoothPatternEntry($entry['name'],
                $entry['methods'], $pattern, $groups, $entry['spec']);
        }
    }
}

class SmoothPatternFileReader implements Iterator {
    const HTTP_METHODS = '(?:GET|HEAD|POST|PUT|DELETE)';
    
    public $filename;
    
    // State:
    private $handle;
    private $line;
    private $last;
    private $done;
    private $counter;
    
    public function __construct($filename) {
        $this->filename = $filename;
    }
    
    private function open() {
        $this->close();
        $h = fopen($this->filename, 'rt');
        if ($h === false) {
            throw new SmoothSetupException('Failed to open routes file "'.
                $this->filename.'".');
        }
        $this->handle = $h;
        $this->line = 0;
        $this->last = null;
        $this->done = false;
        $this->counter = 0;
    }
    
    private function close() {
        if ($this->handle) {
            fclose($this->handle);
            $this->handle = null;
        }
    }
    
    private function readLine() {
        if ($this->last) {
            $line = $this->last;
            $this->last = null;
            return $line;
        }
        
        if (!$this->handle) {
            throw new RuntimeException('Tried to read from a closed file '.
                'in SmoothPatternFileReader.');
        }
        
        while ($line = fgets($this->handle)) {
            ++$this->line;
            // Skip comments and empty lines:
            if (!preg_match('/^\s*(#|$)/', $line))
                break;
        }
        return $line;
    }
    
    private function stashLine($line) {
        if ($this->last !== null) {
            $e = trim($this->last);
            $n = trim($line);
            throw new RuntimeException('Tried to stash the line "'.$n.'" on '.
                'top of "'.$e.'" in SmoothPatternFileReader.');
        }
        $this->last = $line;
    }
    
    public function rewind() {
        $this->open();
    }
    
    public function valid() {
        if (!$this->handle)
            return false;
        $end = feof($this->handle);
        if ($end)
            $this->close();
        return !$end;
    }
    
    public function next() {
        ++$this->counter;
    }
    
    public function key() {
        return $this->counter;
    }
    
    public function current() {
        $line = $this->readLine();
        if (!$line)
            return null;
        
        if (preg_match('/^\s/', $line)) {
            throw new SmoothSetupException('Illegal indentation on line '.
                $this->line.' of routes file "'.$this->filename.'".');
        }
        
        $result = array();
        $matches = array();
        if (preg_match('/(\w+):/', $line, $matches)) {
            $result['name'] = $matches[1];
        } else {
            $result['name'] = null;
        }
        
        $line = preg_replace('/^(-|\w+:)\s*/', '', $line);
        $methods = self::HTTP_METHODS;
        $method_pattern = "/^($methods(?:\\s*,\\s*$methods)*)\s*/";
        if (preg_match($method_pattern, $line, $matches)) {
            $result['methods'] = preg_split('/\s*,\s*/', $matches[1]);
            $line = substr($line, strlen($matches[0]));
        } else {
            $result['methods'] = null;
        }
        
        $parts = preg_split('/\s*=>\s*/', $line, 2);
        
        if (count($parts) == 1) {
            $result['path'] = rtrim($parts[0]);
            $result['spec'] = array();
        } else {
            $result['path'] = $parts[0];
            
            if (preg_match('/^\{.*\}$/', $parts[1])) {
                $result['spec'] = yaml_load($parts[1]);
            } else {
                list($controller, $action) = explode('/', rtrim($parts[1]));
                $result['spec'] = array(
                    'controller' => $controller,
                    'action' => $action
                );
            }
        }
        
        $post = '';
        $indent = null;
        $matches = array();
        while ($line = $this->readLine()) {
            if (!preg_match('/^\s+/', $line, $matches)) {
                // This is not an indented block
                $this->stashLine($line);
                break;
            } else if (!$indent) {
                // Define the indent for the first element; we'll strip it from
                // the starts of all elements.
                $indent = strlen($matches[0]);
            }
            
            $post .= substr($line, $indent);
        }
        
        if (strlen($post) > 0) {
            $result['spec'] = array_merge($result['spec'], yaml_load($post));
        }
        
        return $result;
    }
}

class SmoothPatternEntry {
    private $name;
    private $methods;
    private $pattern;
    private $groups;
    private $config;
    
    public function __construct($name, $methods, $pattern, $groups, $config) {
        $this->name = $name;
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->groups = $groups;
        $this->config = ($config) ? $config : array();
    }
    
    public function match($method, $path) {
        if ($this->methods && !in_array($method, $this->methods))
            return null;
        
        $matches = array();
        if (!preg_match($this->pattern, $path, $matches))
            return null;
        
        $params = array();
        $len = count($matches);
        for ($i = 1; $i < $len; $i++) {
            $match = $matches[$i];
            $group = $this->groups[$i - 1];
            if ($group['pattern'] && !preg_match($group['pattern'], $match))
                return null;
            
            $params[$group['name']] = $match;
        }
        
        return array_merge($this->config, $params);
    }
}
