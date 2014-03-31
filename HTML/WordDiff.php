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
        if(!is_array($config)){
            trigger_error("Word Diff got error the argument IS NOT array");
            return;
        }
        
        foreach($config as $k=>$v){// create the vaild variable checking??
            $this->$k = $v;
        }
        
        $this->_run();
        
    }
    
    function _run()
    {
        $m = 'buildWords_'.$this->lang;
        if(!method_exists($this, $m)){
            trigger_error("Method not found ($m)");
            return;
        }
        $this->$m();
    }
    
    /**
     * set the words array 
     * 
     * for english only 
     * @param $String $target for the array index
     * 
     */
    function buildWords_en($target = 'original')
    {
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
    }
    
    /**
     * set the words array 
     * 
     * 
     * @param $String $target for the array index
     * 
     */
    function buildWords_zh_HK($target = 'original')
    {
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
    }
    
    /**
     * 
     * 
     * 
     * 
     * @param string $article
     * @return int $percent percentage of match 
     * 
     */
    public function compare($article)
    {   
        $m = 'buildWords_'.$this->lang;
        if(!method_exists($this, $m)){
            trigger_error("Method not found ($m)");
            return;
        }
        
        $this->article = $article;
        $this->$m('target');
        
        $matchs = 0;
        
        foreach($this->original as $k=>$t){
            if(isset($this->target[$k])){
                $matchs += ($this->original[$k] == $this->target[$k]) ? $this->original[$k] : $this->original[$k] - $this->target[$k];
            }
        }
        print_r($this->original);
        print_r($this->target);
        $percent = (($matchs / $this->countTotal) * 1) * 100;
        return (int)$percent;
        
    }
    
}
