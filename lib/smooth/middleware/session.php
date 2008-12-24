<?php

// Smooth: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

class SessionMiddleware extends SmoothMiddleware {
    private $name;
    
    public function __construct($name=null) {
        $this->name = ($name) ? $name : 'smooth_session';
    }
    
    public function call(SmoothApplication $application,
        SmoothRequest $request, SmoothResponse $response)
    {
        session_name($this->name);
        session_start();
        
        $request->session_id = session_id();
        $request->session =& $_SESSION;
    }
}
