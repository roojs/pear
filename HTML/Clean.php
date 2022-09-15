<?php

/**
 * This is a PHP implementation of the Roo HTMLEditorCore onPaste method - that cleans up HTML
 * and replaces things like tables etc..
 */

class HTML_Clean {
    
    static function fromHTML($str)
    {
        $str= self::cleanWordChars($str);
        $dom = new DOMDocument('1.0', 'utf8');
        $dom->loadHTML($str);
        return new HTML_Clean($dom);    
    }
    static function cleanWordChars($str)
    {
        $swapCodes  = array(
             8211 =>  "&#8211;" ,  
             8212 =>  "&#8212;" ,  
             8216 =>   "'" ,   
             8217 =>  "'" ,   
             8220 =>  '"' ,   
             8221 =>  '"' ,   
             8226 =>  "*" ,   
             8230 =>  "..." 
        );
        foreach($swapCodes as $k=>$v) {
            $str = str_replace(mb_chr($k), $v, $str);
        }
        return $str;
    
    }
    
    
    var $dom; // Dom Document.
    
    function __construct($dom)
    {
        $this->dom = $dom;
    }
    
    
    
    
}