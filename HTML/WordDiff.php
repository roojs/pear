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
    var $lang = 'en';
    var $article = '';
    var $word = array();
    
    var $alternatives = array(
        '.',
        ',',
        '--'
    );
    
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
//        print_r($config);
//        print_r($this->abc);
        if(!is_array($config)){
            trigger_error("Word Diff got error the argument IS NOT array");
            return;
        }
//        $GLOBALS[__CLASS__] = &$this;
        
        foreach($config as $k=>$v){
//            print_r($v);
//            print_r($k);
//            if(isset($this->$k)){
                $this->$k = $v;
                
//            }
            
            
        }
        
        $this->_run();
        
    }
    
    function _run()
    {
//        print_r($this);
        $m = 'buildWords_'.$this->lang;
        
//        $this->$m();
        
        var_dump(method_exists($this, $m));
        if(method_exists($this, $m)){
            $this->$m();
//            print_R('in?');
        }
//        print_r('???');
    }
    
    public function buildWords_en()
    {
        $a = explode(' ', str_replace($this->alternatives, '', $this->article));
        foreach($a as $str){
//            str_replace($this->alternatives, '', $str);
            print_r(str_replace($this->alternatives, '', $str));
            print_r("\n");
        }
//        print_r('ininin?');
    }
//    
//    function get()
//    {
//        print_r($this);
//        return $GLOBALS[__CLASS__];
//    }
}
