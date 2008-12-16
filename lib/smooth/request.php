<?php

// Smooth: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

class SmoothRequest {
    public $server_address;
    public $server_port;
    public $server_name;
    public $server_software;
    public $protocol;
    
    public $method;
    public $time;
    public $query;
    public $document_root;
    public $secure;
    
    public $accept;
    public $host;
    public $referer;
    public $user_agent;
    public $remote_address;
    
    public $request_uri;
    public $script_name;
    public $path_info;
    
    public function __construct() {
        $this->server_address = $_SERVER['SERVER_ADDR'];
        $this->server_port = $_SERVER['SERVER_PORT'];
        $this->server_name = $_SERVER['SERVER_NAME'];
        $this->server_software = $_SERVER['SERVER_SOFTWARE'];
        $this->protocol = $_SERVER['SERVER_PROTOCOL'];

        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->time = $_SERVER['REQUEST_TIME'];
        $this->query = $_SERVER['QUERY_STRING'];
        $this->document_root = $_SERVER['DOCUMENT_ROOT'];
        $this->secure = (!empty($_SERVER['HTTPS']));

        $this->accept = $_SERVER['HTTP_ACCEPT'];
        $this->host = $_SERVER['HTTP_HOST'];
        $this->referer = @$_SERVER['HTTP_REFERER'];
        $this->referrer =& $this->referer;
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $this->remote_address = $_SERVER['REMOTE_ADDR'];

        $this->request_uri = $_SERVER['REQUEST_URI'];
        $this->script_name = $_SERVER['SCRIPT_NAME'];
        $this->script_file = $_SERVER['PHP_SELF'];
        $this->path_info = $_SERVER['PATH_INFO'];
        
        // It's possible to use Apache's mod_rewrite or something similar to
        // give the local path to Smooth via a _path_info GET variable. If that
        // is happening, clean up our request environment to make it more
        // CGI-like.
        if (isset($_GET['_path_info'])) {
            $this->path_info = $_GET['_path_info'];
            
            $path = $this->path_info;
            $len = strlen($path);
            if ($len == 0 || substr($this->request_uri, -$len) == $path) {
                $this->script_name = substr($this->request_uri, 0, -$len);
                if ($path[0] != '/' && substr($this->script_name, -1) == '/') {
                    $this->script_name = substr($this->script_name, 0, -1);
                    $this->path_info = '/'.$this->path_info;
                }
            }
        }
    }
    
    public function getAcceptableTypes() {
        if ($this->acceptable_types)
            return $this->acceptable_types;
        
        static $type_pattern =
            '/((?:[\w-]+|\*)\/(?:[\w-\+]+|\*))(?:;\s*q\s*=\s*(\d(?:\.\d+)?))?/';
        
        $matches = array();
        $count = preg_match_all($type_pattern, $this->accept, $matches,
            PREG_SET_ORDER);
        
        $types = array();
        foreach ($matches as $match) {
            $quality = ($match[2]) ? ((float) $match[2]) : 1.0;
            $types[] = array($match[1], $quality);
        }
        usort($types, array('SmoothRequest', 'compareTypes'));
        
        $this->acceptable_types = array();
        foreach ($types as $type) {
            $this->acceptable_types[] = $type[0];
        }
        
        return $this->acceptable_types;
    }
    
    public function getPreferredType() {
        $providable = func_get_args();
        $acceptable = $this->getAcceptableTypes();
        
        foreach ($acceptable as $at) {
            foreach ($providable as $pt) {
                if ($this->typesMatch($pt, $at))
                    return $pt;
            }
        }
        
        return null;
    }
    
    private function compareTypes($a, $b) {
        if ($a[1] == $b[1]) 
            $res = 0;
        else
            $res = ($a[1] < $b[1]) ? 1 : -1;
        return $res;
    }
    
    private function typesMatch($p, $a) {
        $p = explode('/', $p);
        $a = explode('/', $a);
        
        return ($a[0] == '*' || $p[0] == $a[0]) &&
            ($a[1] == '*' || $p[1] == $a[1]);
    }
}
