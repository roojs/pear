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
    var $original = array();
    var $target = array();
    var $countTotal = 0;
    
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
        
        foreach($config as $k=>$v){// create the vaild variable??
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
            trigger_error("Method not found ($m)");
            return;
        }
        $this->$m();
    }
    
    /**
     * set the words array 
     * 
     * 
     * @param $String $target for the array index
     * 
     */
    function buildWords_en($target = 'original')
    {
        
//        $t = str_replace($this->alternatives, '', $this->article);
//        $titleTest = 
//        if(preg_match('/media outreach/i',$t,$matches)){
//            
//        }
//        $target = 'original';
//        print_r($target);
        $a = explode(' ', str_replace($this->alternatives, '', $this->article));
        $ret = array();
        foreach($a as $str){
            if($target == 'original'){
                $this->countTotal++;
            }
            if(!isset($ret[$str])){
                $ret[$str] = 1;
                continue;
            }
            $ret[$str] += 1;
        }
        $this->$target = $ret;
//        print_r($this->word);
//        print_r('ininin?');
    }
    
    public function compare($article)
    {   
        $m = 'buildWords_'.$this->lang;
        if(!method_exists($this, $m)){
            trigger_error("Method not found ($m)");
            return;
        }
        
        $this->article = $article;
        $this->$m('target');
//        $countTotal = 0;
//        foreach($a as $str){
//            $countTotal++;
//            if(!isset($this->target[$str])){
//                $this->target[$str] = 1;
//                continue;
//            }
//            $this->target[$str] += 1;
//        }
        
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
        
        $matchs = 0;
//        print_r("tt : ". $countTotal);
        print_r("\n");
        foreach($this->original as $k=>$t){
//            $countTotal += $this->word[$k];
            if(isset($this->target[$k])){
                $matchs += ($this->original[$k] == $this->target[$k]) ? $this->original[$k] : $this->original[$k] - $this->target[$k];
                //($this->target[$k] / $this->word[$k]) * 1;
                print_r($this->original[$k] - $this->target[$k]);
//                print_r($k." : ".$this->target[$k]. " / ". $this->word[$k] . " * 1 => ".($this->target[$k] / $this->word[$k]) * 1);
                print_r("\n");
            }
        }
        
//        $a / $b * 1;
        print_r(($matchs / $this->countTotal) * 1);
        print_r("\n");
        print_r($this->countTotal);
        print_r("\n");
        print_r($matchs);
        print_r("\n");
        return ($matchs / $this->countTotal) * 1;
        
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
