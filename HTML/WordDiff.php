<?php

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
    
    var $config;
    var $lang;
    var $words;
    
    function __construct($config)
    {
        print_r($config);
        print_r($this);
//        $GLOBALS[__CLASS__] = &$this;
        $this->config = $config;
        $this->_run($config['words'], $config['lang']);
        
    }
    
    function _run($words, $opt, $lang = 'en')
    {
        
    }
    
    function countWords_en()
    {
        
    }
    
    function get()
    {
        print_r($this);
        return $GLOBALS[__CLASS__];
    }
}
