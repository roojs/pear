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
    var $targetTotal = 0; // Total words count form target html
    var $wordMax = -1;
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
    var $debug_on = false;
    /**
     * Constructor
     * 
     * 
     * @param Array $config
     * lang = language of article
     * file = name of file...
     * string = string contents
     * 
     * @return type
     * 
     */
    function __construct($config = false)
    {
        //print_r($config);
        
        if(!$config){
            return;
        }
        
        if(!is_array($config)){
            trigger_error("Word Diff got error, the argument IS NOT array");
            return;
        }
        
        if(empty($config['lang'])){
            trigger_error("the language is missing.");
            return;
        }
        if(empty($config['file']) && !isset($config['string'])){
            trigger_error("File is missing");
            return;
        }
        
        // not in used now??
        if(!in_array($this->lang, $this->nonSinoTibetan)){
            if(!in_array($this->lang, $this->sinoTibetan)){
                trigger_error("This ({$this->lang}) language is not on our word type classification");
            }
            return;
        }
        
        
        $this->htmlDom = isset($config['string']) ? $config['string'] : '';
        
        
        if(isset($config['file']) && file_exists($config['file'])){
            $this->htmlDom = file_get_contents($config['file']);
        }
        
        $this->lang = $config['lang'];
        
    
        $m = 'buildWords';// default run sino-tibetan
        
        if(!method_exists($this, $m)){
            trigger_error("Method not found ($m)");
            return;
        }
        $this->$m();
    }
    
    function isSino()
    {
        return in_array($this->lang, $this->sinoTibetan);
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
        static $cache= array();
        if (isset($cache[md5($this->htmlDom)])) {
            $this->$target = $cache[md5($this->htmlDom)];
            
            if ($this->wordMax < 0) {
                $this->wordMax = array_sum(array_values($this->target)) * 10 ;
            }
            
            if($target == 'original'){
                $this->countTotal = array_sum(array_values($this->$target));
            }else{
                $this->targetTotal= array_sum(array_values($this->$target));
            }
            
            return;
        }
        
        $a = $this->DomToStrings();
        
        if ($this->wordMax < 0) {
            $this->wordMax = 10*count($a);
        }
        if($this->debug_on){
//            print_r($a);
            //exit;
        }
        $ret = array();
        $last_w = false;
        
        foreach($a as $str){
            if(empty($str) || !trim(strlen($str))) {
                continue;
            }
            if(!isset($ret[$str])){
                $ret[$str] = 1;
            
            } else {
                $ret[$str] += 1;
            }
            // now deal with pairing..
            if ($last_w !== false) {
                
                if(!isset($ret[$last_w.'|'.$str])){
                    $ret[$last_w.'|'.$str] = 1;

                } else {
                    $ret[$last_w.'|'.$str] += 1;
                }    
                
            }
            $last_w = $str;
        }
        
        if($target == 'original'){
            $this->countTotal = array_sum(array_values($ret));
        }else{
            $this->targetTotal= array_sum(array_values($ret));
        }
        $this->$target = $ret;
        $cache[md5($this->htmlDom)] = $ret;
    }
    
    function DomToStrings($target = '')
    {
        
        $pageDom = new DomDocument('1.0', 'UTF-8');
        $pageDom->formatOutput = true;
//        print_r(mb_detect_encoding($this->htmlDom));
        $searchPage = mb_convert_encoding($this->htmlDom, "UTF-8",  "auto");
//        $searchPage = mb_convert_encoding($this->htmlDom, "UTF-8",  "HTML-ENTITIES");
//        echo $searchPage;
//        print_r(mb_detect_encoding($searchPage));
        
//        $searchPage = mb_convert_encoding($this->htmlDom, "big5");
//        if($target == 'target'){
//            print_r($searchPage);
//            exit;
//        }
//        print_r(mb_detect_encoding($searchPage));
      // print_r($searchPage);
       @ $pageDom->loadHTML('<?xml version="1.0" encoding="UTF-8"?>' . $searchPage);
//        exit;
        $words = $this->domExtractWords($pageDom->documentElement, array());
       // print_r($words);exit;
        
//        $string = preg_replace('/[^\pL\pS\pN]/u', '-', $pageDom->documentElement->getElementsByTagName('body')->item(0)->textContent);
        if($this->debug_on){
            print_r('?????      ');
            print_r($words);
             print_r($searchPage);
//            exit;
        }
        return $words;
    }
    
    
    
    var $tmpWords = false;
    function addUTF8Word($s) {
        $this->tmpWords[] = $s[0];
//        print_r($this->tmpWords);
        return ' ';
    }
    
    function domExtractWords($node, $words)
    {
        if ($this->wordMax > 0 && count($words) >  $this->wordMax) {
            return $words;
        }
        //echo count($words) ."\n";
        if (empty($node)) {
            return $words;
        }
        if ($node->nodeType == XML_TEXT_NODE &&strlen(trim($node->textContent))) {// this is got the bug at sina....
            
            $str = trim($node->textContent);
            //var_dump('xx'.$str);
            //var_dump($str);
            $this->tmpWords = $words;
            //if ($this->isSino()) {
               $str = preg_replace_callback('/'.$this->cjkpreg().'/u', array($this, 'addUTF8Word')  , $str);
            //}
            $words = $this->tmpWords;
            // remove puncutianion..
            $str = preg_replace('/[^\w]+/u', ' ', $str);
            
            foreach(preg_split('/\s+/u', $str) as $word) {
                if($this->debug_on){
//                    print_r(mb_detect_encoding($node->textContent));
                    //print_r("\n");
                }
                if (!trim($word)) {
                    continue;
                }
                // fixme - break unicode chars
                $words[] = $word;
            }
            
        }
        if (!$node->hasChildNodes()) {
            return $words;
        }
        
        for($i = 0; $i < $node->childNodes->length; $i++) {
            
            $n = $node->childNodes->item($i);
            //if($this->debug_on){
            //    print_r($n);
            //    print_r("\n");
            //}
            $words = $this->domExtractWords($n, $words);
        }
        return $words;
        
        
    }
    function cjkpreg() {
        
        static $ret = false;
        if ($ret !== false) {
            return $ret;
        }
        
        $ret = '['.implode('', array(
                    "\x{2E80}-\x{2EFF}",      # CJK Radicals Supplement
                    "\x{2F00}-\x{2FDF}",      # Kangxi Radicals
                    "\x{2FF0}-\x{2FFF}",      # Ideographic Description Characters
                    "\x{3000}-\x{303F}",      # CJK Symbols and Punctuation
                    "\x{3040}-\x{309F}",      # Hiragana
                    "\x{30A0}-\x{30FF}",      # Katakana
                    "\x{3100}-\x{312F}",      # Bopomofo
                    "\x{3130}-\x{318F}",      # Hangul Compatibility Jamo
                    "\x{3190}-\x{319F}",      # Kanbun
                    "\x{31A0}-\x{31BF}",      # Bopomofo Extended
                    "\x{31F0}-\x{31FF}",      # Katakana Phonetic Extensions
                    "\x{3200}-\x{32FF}",      # Enclosed CJK Letters and Months
                    "\x{3300}-\x{33FF}",      # CJK Compatibility
                    "\x{3400}-\x{4DBF}",      # CJK Unified Ideographs Extension A
                    "\x{4DC0}-\x{4DFF}",      # Yijing Hexagram Symbols
                    "\x{4E00}-\x{9FFF}",      # CJK Unified Ideographs
                    "\x{A000}-\x{A48F}",      # Yi Syllables
                    "\x{A490}-\x{A4CF}",      # Yi Radicals
                    "\x{AC00}-\x{D7AF}",      # Hangul Syllables
                    "\x{F900}-\x{FAFF}",      # CJK Compatibility Ideographs
                    "\x{FE30}-\x{FE4F}",      # CJK Compatibility Forms
                    "\x{1D300}-\x{1D35F}",    # Tai Xuan Jing Symbols
                    "\x{20000}-\x{2A6DF}",    # CJK Unified Ideographs Extension B
                    "\x{2F800}-\x{2FA1F}"     # CJK Compatibility Ideographs Supplement
        )). ']';
        
//        print_R($ret);
        return $ret;
    }
    
    /**
     * 
     * 
     * 
     * 
     * @param (array|string) $file either file path or array('string'=>'....')
     * 
     * @return int $percent percentage of match 
     * 
     */
    public function compare($file)
    {
        
        if (is_array($file)) {
            $this->htmlDom = $file['string'];
        }
        
        $this->debug_on = true;
//        print_r('is target');
        if(is_string($file) && file_exists($file)){
            $this->htmlDom = file_get_contents($file);
        }
        
        $m = 'buildWords';
        if(!method_exists($this, $m)){
            trigger_error("Method not found ($m)");
            return;
        }
        
        //print_r($this);
        $this->$m('target');
        
        $matchs = 0;
     //  print_r($this->original);
     //  print_r($this->target);// exit;
        foreach($this->original as $k=>$t){
            if(!isset($this->target[$k])){
                continue;
            }
            
//                $matchs += $this->original[$k] + $this->target[$k];
            if($this->original[$k] == $this->target[$k]){
                $matchs += $this->original[$k];
                continue;
            }
            
            if($this->original[$k] > $this->target[$k]){
                $matchs += $this->target[$k];
                continue;
            }
            $matchs += $this->original[$k];
            
        }
//        print_r($matchs);
//        print_r("\n");
//        print_R(($this->countTotal + $this->targetTotal));  
//        print_r("\n");
        $percent = ( $matchs / ($this->countTotal) * 100);
        return (int)$percent;
        
    }
    
}
