<?php

// Smooth: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

// This file just contains some housekeeping; for the real meat see
// lib/smooth.php.

function _raise_from_error($severity, $message, $file, $line) {
    if (error_reporting() & $severity)
        throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler("_raise_from_error", E_ERROR | E_WARNING | E_CORE_ERROR |
    E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR);

function path_join() {
    $parts = func_get_args();
    return implode(DIRECTORY_SEPARATOR, $parts);
}

define('SMOOTH_ROOT', dirname(__FILE__));
function smooth_path() {
    $parts = func_get_args();
    return path_join(SMOOTH_ROOT, call_user_func_array('path_join', $parts));
}

function _smooth_get_lib_path($library) {
    return path_join(SMOOTH_ROOT, 'lib', 'smooth', "$library.php");
}

function _smooth_transform_path($path) {
    return str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function smooth_load() {
    $libs = func_get_args();
    if (DIRECTORY_SEPARATOR != '/')
        $libs = array_map('_smooth_transform_path', $libs);
    $paths = array_map('_smooth_get_lib_path', $libs);
    foreach ($paths as $path) {
        require_once $path;
    }
}

// Let's get this started!
require_once smooth_path('support', 'yaml.php');
require_once smooth_path('lib', 'smooth.php');
