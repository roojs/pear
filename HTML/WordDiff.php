<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WordDiff
 *
 * @author chris
 */
//
//require_once 'PEAR.php';
//require_once 'DB/DataObject.php';

class HTML_WordDiff
{
    //put your code here
    
    function __construct($config)
    {
        print_r($config);
        $GLOBALS[__CLASS__] = &$this;
    }
    
    function get()
    {
        print_r($this);
        return $GLOBALS[__CLASS__];
    }
}
