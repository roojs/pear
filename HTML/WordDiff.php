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
    var $lang = 'en'; // the press release language
    var $article = '';// not in used?
    var $original = array(); // original html words
    var $target = array(); // diff target html words
    var $countTotal = 0; // Total words count form original html
    //word type classification
    var $nonSinoTibetan = array(//non Sino-Tibetan languages
        'aa',
        'ab',
        'en',
        'pt',
        'ar',
        'de',
        'fr',
        'es',
        'vi',
        'id',
    );
    var $sinoTibetan = array(//Sino-Tibetan languages
        'my',
        'th',
        'ko',
        'zh_HK',
        'ja',
        'zh_TW',
        'zh_CN',
    );
    
    var $alternatives = array(
        '.',
        ',',
        '--'
    );
    
    var $htmlDom = false; // HTML Dom elements
    
    /**
     * Constructor
     * 
     * 
     * @param Array $config
     * lang = language of article
     * file = HTML file path or string
     * 
     * @return type
     * 
     */
    function __construct($config)
    {
        if(!is_array($config)){
            trigger_error("Word Diff got error, the argument IS NOT array");
            return;
        }
        
        if(empty($config['lang'])){
            trigger_error("the language is missing.");
            return;
        }
        if(empty($config['file'])){
            trigger_error("Article is missing");
            return;
        }
        
        if(!in_array($this->lang, $this->nonSinoTibetan)){
            if(!in_array($this->lang, $this->sinoTibetan)){
                trigger_error("This ({$this->lang}) language is not on our word type classification");
            }
            return;
        }
        
        $this->htmlDom = $config['file'];
        
        if(file_exists($config['file'])){
            $this->htmlDom = file_get_contents($config['file']);
        }
        
        $this->lang = $config['lang'];
        
        $this->_run();
        
    }
    
    function _run()
    {
        $m = 'buildWords';// default run sino-tibetan
        
        if(in_array($this->lang, $this->sinoTibetan)){
            $m = 'buildWordsSino';// run the Sino-Tibetan
        }
        
        if(!method_exists($this, $m)){
            trigger_error("Method not found ($m)");
            return;
        }
        $this->$m();
    }
    
    /**
     * set the words array 
     * 
     * for non Sino-Tibetan languages etc. English, French
     * 
     *  
     * @param $String $target for the array index
     * 
     */
    function buildWords($target = 'original')
    {
        $str = $this->DomToStrings();
        return;
//        $var_1 = 'PHP IS GREAT'; 
//        $var_2 = 'WITH MYSQL'; 
//
//        similar_text($var_2, $var_1, $percent); 
//
//        echo $percent."\n"; 
//        return;
        //remove URLs
        $t = preg_replace('/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/i', '', $this->article);
        
        //removes special chars
        $string = str_replace(' ', '-', $t);
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);

        $a = explode('-', $string);
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
     * for Sino-Tibetan languages etc. chinese, japanese
     * 
     * @param $String $target for the array index
     * 
     */
    function buildWordsSino($target = 'original')
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
    
    function DomToStrings()
    {
        print_r($this->htmlDom);
        
        $pageDom = new DomDocument('1.0', 'utf-8');    
        $pageDom->formatOutput = true;
        $searchPage = mb_convert_encoding($this->htmlDom, 'HTML-ENTITIES', "UTF-8");
        @$pageDom->loadHTML($searchPage);
        
        $ret = array();
        $count = 1;
        
        $xp = new DOMXPath($pageDom);
        //$q = "id('web')/ol/li/div"; 
        $q = "//body";
        $lists = $xp->query($q);
        print_r(mb_convert_encoding($pageDom->saveHTML($lists->item(0)), 'HTML-ENTITIES', "UTF-8"));
        exit;
        return $string;
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
