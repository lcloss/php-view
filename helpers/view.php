<?php
use LCloss\Route\Request;

if ( !function_exists('request')) 
{
    function request() {
        return new Request();
    }
}