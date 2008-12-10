<?php

// SMOOTH: The PHP framework that goes down easy.
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
            $match = $this->table[$i]->match($request->path_info);
            if (!$match)
                continue;
            
            return $match;
        }
        
        return false;
    }
    
    private function loadRoutes() {
        $file = $this->getRouteFile();
        if (!$file) {
            throw new SmoothSetupException('No routes file exists.');
        }
        
        $raw = yaml_load($file);
        foreach ($raw as $name => $route) {
            if (!is_array($route)) {
                throw new SmoothSetupException('Invalid route "'.$route.'".');
            }
            
            $path = array_keys($route);
            $path = $path[0];
            $spec = $route[$path];
            
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
                    $group['pattern'] = '/'.$m[1][0].'/';
                }
                $groups[] = $group;
                $pattern .= '(.+?)';
                $last = $m[0][1] + strlen($m[0][0]);
            }
            
            $pattern .= preg_quote(substr($path, $last), '#');
            if (substr($pattern, -1) != '/')
                $pattern .= '/?';
            $pattern .= '$#';
            
            $this->table[] = new SmoothPatternEntry($pattern, $groups, $spec);
        }
    }
}

class SmoothPatternEntry {
    private $name;
    private $pattern;
    private $groups;
    private $config;
    
    public function __construct($name, $pattern, $groups, $config) {
        $this->name = $name;
        $this->pattern = $pattern;
        $this->groups = $groups;
        $this->config = $config;
    }
    
    public function match($path) {
        $matches = array();
        if (!$len = preg_match($this->pattern, $path, $matches))
            return null;
        
        $params = array();
        for ($i = 0; $i < $len; $i++) {
            $match = $matches[$i];
            $group = $this->groups[$i];
            if ($group['pattern'] && !preg_match($group['pattern'], $match))
                return null;
            
            $params[$group['name']] = $match;
        }
        
        return array_merge($this->config, $params);
    }
}
