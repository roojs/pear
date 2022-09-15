<?php

/**
 * This is a PHP implementation of the Roo HTMLEditorCore onPaste method - that cleans up HTML
 * and replaces things like tables etc..
 */

class HTML_Clean {
    
    static function fromHTML($str)
    {
        $dom = new DOMDocument('1.0', 'utf8');
        $dom->loadHTML($str);
        return new HTML_Clean($dom);    
    }
    
    var $dom; // Dom Document.
    
    function __construct($dom)
    {
        $this->dom = $dom;
    }
    
    
    
    
}