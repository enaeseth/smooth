<?php

// SMOOTH: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

smooth_load('request');

abstract class SmoothRouter {
    protected $application;
    
    public function __construct(SmoothApplication $application) {
        $this->application = $application;
    }
    
    public abstract function route(SmoothRequest $request);
    // public abstract function getPath($controller, $action);
    
    protected function getRouteFile($extensions=null) {
        if (!$extensions) {
            $extensions = array('yml', 'yaml', 'conf', 'php', 'txt');
        }
        $extensions = implode(',', $extensions);
        
        $pattern = path_join($this->application->root, '{config,configuration}',
            'routes.{'.$extensions.'}');
        $files = glob($pattern, GLOB_BRACE);
        
        if (empty($files))
            return null;
        if (!is_readable($files[0])) {
            throw new SmoothSetupException('Routes file "'.
                $files[0].'" is not readable.');
        }
        return $files[0];
    }
    
    public static function get($router, SmoothApplication $application) {
        if ($router == 'default')
            $router = 'pattern';
        
        switch ($router) {
            case 'pattern':
                smooth_load('routers/pattern');
                return new SmoothPatternRouter($application);
            default:
                throw new SmoothSetupException('Unknown Smooth router '.
                    '"'.$router.'".');
        }
    }
}
