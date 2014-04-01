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
    var $htmlDom = false; // HTML Dom elements
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
        
        if(!in_array($this->lang, $this->wordTypeLanguage)){
            trigger_error("This language is not on our word type classification");
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
        
//        $text = preg_replace("
//  #((http|https|ftp)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#ie",
//  "'<a href=\"$1\" target=\"_blank\">$3</a>$4'",
//  $text
//);
    }
    
    /**
     * set the words array 
     * 
     * 
     * @param $String $target for the array index
     * 
     */
    function buildWords_chinese($target = 'original')
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
