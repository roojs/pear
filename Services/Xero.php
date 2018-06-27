<?php

/*
 * Xero Library
 * https://developer.xero.com/documentation/libraries/php
 * https://github.com/XeroAPI/XeroOAuth-PHP
 * 
 */

require_once 'Services/Xero/OAuth.php';

class Services_Xero
{
    var $XeroOAuth;
    
    function __construct($config)
    {
        $this->XeroOAuth = new Services_Xero_OAuth($config);
    }
}

