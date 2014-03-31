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
    var $target = array();
    
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
    
    function buildWords_en()
    {
        
//        $t = str_replace($this->alternatives, '', $this->article);
//        $titleTest = 
//        if(preg_match('/media outreach/i',$t,$matches)){
//            
//        }
//        print_r($t);
        $a = explode(' ', str_replace($this->alternatives, '', $this->article));
        foreach($a as $str){
            if(!isset($this->word[$str])){
                $this->word[$str] = 1;
                continue;
            }
            $this->word[$str] += 1;
        }
//        print_r($this->word);
//        print_r('ininin?');
    }
    
    public function compare($article)
    {
        
        $a = explode(' ', str_replace($this->alternatives, '', $article));
//        $b = explode(' ', str_replace($this->alternatives, '', $article));
//        $test = array();
        
        foreach($a as $str){
            if(!isset($this->target[$str])){
                $this->target[$str] = 1;
                continue;
            }
            $this->target[$str] += 1;
        }
        
//        $a = array();
//        $b = array();
//        
//        if(count($this->word) > count($test)){
//            $a = $this->word;
//            $b = $test;
//        }else{
//            $a = $test;
//            $b = $this->word;
//        }
        
        $matchs = array();
        foreach($this->word as $k=>$t){
            if(isset($this->target[$k])){
                $matchs[$k] = ($this->word[$k] / $this->target[$k]) * 1;
                print_r($this->word[$k]. " / ". $this->target[$k] . " * 1");
            }
        }
        print_r($matchs);
        print_r("\n");
//        print_r(count($this->word));
        
//        print_R($test);
    }
    
//    
//    function get()
//    {
//        print_r($this);
//        return $GLOBALS[__CLASS__];
//    }
}
