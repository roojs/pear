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
    
//    var $config;
    var $lang;
    var $words;
    
    /**
     * Constructor
     * 
     * 
     * @param Array $config
     * @return type
     * 
     */
    function __construct($config)
    {
        print_r($config);
//        print_r($this->abc);
        if(!is_array($config)){
            trigger_error("Word Diff got error the argument IS NOT array");
            return;
        }
//        $GLOBALS[__CLASS__] = &$this;
        
        foreach($config as $k=>$v){
            print_r($v);
            print_r($k);
            if(!isset($this->$k)){
                continue;
            }
            
            $this->$k = $v;
        }
        
        $this->_run();
        
    }
    
    function _run()
    {
        print_r($this);
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
