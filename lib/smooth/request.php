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
        $this->referer = $_SERVER['HTTP_REFERER'];
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $this->remote_address = $_SERVER['REMOTE_ADDR'];

        $this->request_uri = $_SERVER['REQUEST_URI'];
        $this->script_name = $_SERVER['SCRIPT_NAME'];
        $this->path_info = $_SERVER['PATH_INFO'];
        
        if (isset($_GET['_path_info'])) {
            $this->path_info = $_GET['_path_info'];
        }
    }
}
