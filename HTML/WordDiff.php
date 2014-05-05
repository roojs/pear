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
//            $m = 'buildWordsSino';// run the Sino-Tibetan
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
        $a = $this->DomToStrings();
        
        $ret = array();
        foreach($a as $str){
            if(empty($str)){
                continue;
            }
            if($target == 'original'){
                $this->countTotal++;
            }else{
                $this->targetTotal++;
            }
            if(!isset($ret[$str])){
                $ret[$str] = 1;
                continue;
            }
            $ret[$str] += 1;
        }
        if($target == 'target'){
            print_r($ret);
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
        $this->$target = implode('', $this->DomToStrings());
    }
    
    function DomToStrings()
    {
        
        $pageDom = new DomDocument('1.0', 'utf-8');    
        $pageDom->formatOutput = true;
//        print_r(mb_detect_encoding($this->htmlDom));
//        $searchPage = mb_convert_encoding($this->htmlDom, "UTF-8");
//        print_r(mb_detect_encoding($searchPage));
        $searchPage = mb_convert_encoding($this->htmlDom, "UTF-8", 'HTML-ENTITIES');
//        print_r(mb_detect_encoding($searchPage));
//        print_r($searchPage);exit;
        @$pageDom->loadHTML($searchPage);
//        exit;
        $words = $this->domExtractWords($pageDom->documentElement, array());
//        print_r($words);exit;
        //$string = preg_replace('/[^\pL\pS\pN]/u', '-', $pageDom->documentElement->getElementsByTagName('body')->item(0)->textContent);
        
        return $words;
    }
    
    function domExtractWords($node, $words)
    {
        
        if (empty($node)) {
            return $words;
        }
        if ($node->nodeType == XML_TEXT_NODE) {
        
            foreach(preg_split('/\s+/', $node->nodeValue) as $word) {
                $words[] = $word;
            }
            
        }
        if (!$node->hasChildNodes()) {
            return $words;
        }
        
        for($i = 0; $i < $node->childNodes->length; $i++) {
            $n = $node->childNodes->item($i);
            $words = $this->domExtractWords($n, $words);
        }
        return $words;
        
        
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
//        if(in_array($this->lang, $this->sinoTibetan)){
//            $m = 'buildWordsSino';// run the Sino-Tibetan
//        }
        if(!method_exists($this, $m)){
            trigger_error("Method not found ($m)");
            return;
        }
        //print_r($this);
        $this->$m('target');
        
//        if($m == 'buildWordsSino'){
//            echo "ORIGINAL: $this->original \n \n TARGET: $this->target\n\n";
//            
//            similar_text($this->original, $this->target, $p1);
//            similar_text($this->target, $this->original, $p2);
//            return ($p1 > $p2) ? (int)$p1 : (int)$p2;
//        }
//        echo "ORIGINAL: ".print_r($this->original) ." \n \n TARGET: ". print_r($this->target) . "\n\n";
        $matchs = 0;
        print_r($this->original);
        print_r($this->target);exit;
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
