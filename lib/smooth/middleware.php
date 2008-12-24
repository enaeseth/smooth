<?php

// Smooth: The PHP framework that goes down easy.
// Copyright © 2008 Carleton College.

abstract class SmoothMiddleware {
    public abstract function call(SmoothApplication $application,
        SmoothRequest $request, SmoothResponse $response);
}
