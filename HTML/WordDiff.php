<?php

/**
 * Description of WordDiff
 *
 *  require_once 'HTML/WordDiff.php';
 *       $init = array(
 *           'lang' => 'en',
 *           'file' => '/home/press/rss/2014/03/31/3952.html'
 *       );
 *       $wd = new HTML_WordDiff($init);
 *        $percent = $wd->compare('/home/press/rss/2014/03/31/3954.html');
 * 
 * 
 * 
 * @author chris
 */
//
//require_once 'PEAR.php';
//require_once 'DB/DataObject.php';

class HTML_WordDiff
{
    //put your code here
    
    var $lang = 'en'; // the press release language
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
            trigger_error("File is missing");
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
        
        $a = explode('-', $str);
        $ret = array();
        foreach($a as $str){
            if(empty($str)){
                continue;
            }
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
        $this->$target = $this->DomToStrings();
    }
    
    function DomToStrings()
    {
        $clear = html_entity_decode($str, 48, 'UTF-8');
        $pageDom = new DomDocument('1.0', 'utf-8');    
        $pageDom->formatOutput = true;
        $searchPage = mb_convert_encoding($this->htmlDom, 'HTML-ENTITIES', "UTF-8");
        @$pageDom->loadHTML($searchPage);
        $ss = mb_convert_encoding($pageDom->documentElement->getElementsByTagName('body')->item(0)->nodeValue, 'HTML-ENTITIES', "UTF-8");
        $string = preg_replace('/[^\pL\pS\pN]/u', '-', $ss);
        return $string;
        
        // Strip HTML Tags
        $string = strip_tags($this->htmlDom);
        // Clean up things like &amp;
        $string = html_entity_decode($string);
        // Strip out any url-encoded stuff
        $string = urldecode($string);
        // Replace non-AlNum characters with space
        $string = preg_replace('/[^A-Za-z0-9]/', ' ', $string);
        // Replace Multiple spaces with single space
        $string = preg_replace('/ +/', ' ', $string);
        // Trim the string of leading/trailing space
        $string = trim($string);
        
//        $string = preg_replace('/[^\pL\pS\pN]/u', '-', $ss);
        return $string;
        
    }
    
    /**
     * 
     * 
     * 
     * 
     * @param string/file path $file
     * @return int $percent percentage of match 
     * 
     */
    public function compare($file)
    {
        $this->htmlDom = $file;
        
        if(file_exists($file)){
            $this->htmlDom = file_get_contents($file);
        }
        
        $m = 'buildWords';
        if(in_array($this->lang, $this->sinoTibetan)){
            $m = 'buildWordsSino';// run the Sino-Tibetan
        }
        if(!method_exists($this, $m)){
            trigger_error("Method not found ($m)");
            return;
        }
        
        $this->$m('target');
        
        if($m == 'buildWordsSino'){
            similar_text($this->original, $this->target, $p1);
            similar_text($this->target, $this->original, $p2);
            return ($p1 > $p2) ? (int)$p1 : (int)$p2;
        }
        
        $matchs = 0;
        
        foreach($this->original as $k=>$t){
            if(isset($this->target[$k])){
                $matchs += ($this->original[$k] == $this->target[$k]) ? $this->original[$k] : $this->original[$k] - $this->target[$k];
            }
        }
        
        $percent = (($matchs / $this->countTotal) * 1) * 100;
        return (int)$percent;
        
    }
    
}
