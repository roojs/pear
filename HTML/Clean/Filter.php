<?php

/**
 * Base class for all the filtering, contains a few usefull routines along with the walk code
 * 
 * doesnt really use filter parent much
 *
 */ 

class  HTML_Clean_Filter
{
    var $replaceComment = false; // default to trash these.!
    
    var $node = false;
    var $tag = false;
   
    function __construct($cfg)
    {
        foreach($cfg as $k=>$v) {
            $this->$k = $v;
        }
    }
    
    function walk ($dom)
    {   
        $ar = $this->arrayFrom($dom->childNodes);
        foreach($ar as $e) {
        
            switch(true) {
                case $this->isTagMatch($e):
                    if (false === $this->replaceTag($e)) {
                        continue 2; // Continue the foreach loop, not the switch
                    }
                    if ($e->hasChildNodes()) {
                        $this->walk($e);
                    }
                    continue 2; // Continue the foreach loop, not the switch
                default:    // tags .. that do not match.
                    if ($e->hasChildNodes()) {
                        $this->walk($e);
                    }
            }
            
        }
        
    }
    function isTagMatch($e) {
        switch(true) {
            
            case $e->nodeType == 8 &&  $this->replaceComment  !== false: // comment
                    $this->replaceComment($e);
                    return false;
                
                case $e->nodeType != 1: //not a node.
                    return false;
            
            case $this->tag === true: // everything
            case strpos(':', $e->tagName) !== false && is_array($this->tag) && in_array(":", $this->tag):
            case strpos(':', $e->tagName) !== false && is_string($this->tag)  && $this->tag == ":":
            case is_array($this->tag) && in_array($e->tagName, $this->tag):
            case is_string($this->tag) && $e->tagName ==  $this->tag:
                return true;
        }
         
    }
    
    
    // dummy version - implementations should return false to not walk children.
    function replaceTag($e) {
        // if we avoid filtering here -> we could just call walk on all the child names.
        
        return true;
    }
    
    function removeNodeKeepChildren  ( $node)
    {
    
        $ar = $this->arrayFrom($node->childNodes);
        foreach($ar as $n) {
            $node->removeChild($n);
            $node->parentNode->insertBefore($n, $node);
        }
        $node->parentNode->removeChild($node);
    }
    
    function arrayFrom($list)
    {
        $ret = array();
        foreach($list as $k=> $l) {
            $ret[$k] = $l;
        }
        return $ret;
    }
    
    function innerHTML($n)
    {
        $ret = "";
        foreach($n->children as $c) {
            $ret .= $c->ownerDocument->saveXML($c);
        }
        return $ret;
    }
    
    function copyInnerHTML($from, $to)
    {
        $ar = $this->arrayFrom($from->childNodes);
        foreach($ar as $n) {
            $from->removeChild($n);
            $to->appendChild($n);
        }
    }
    
    function styleToObject($node, $lower = false)
    {
        $styles = explode(';',$node->hasAttribute("style") ? $node->getAttribute("style")  : '');
        $ret = array();
        foreach($styles as $s) {
            if (strpos($s, ':') === false) {
                return;
            }
            $kv = explode(':', $s, 2);
             
            // what ever is left... we allow.
            $ret[$lower ? strtotrim($kv[0]) : $kv[0]] = trim($kv[1]);
        }
        return $ret;
    }
    function nodeSetStyle($node, $style)
    {
        $str = array();
        foreach($style as $k=>$v) {
            $str[] = "$k:$v";
        }
        $node->setAttribute('style', implode(";", $str));
    }
    
}
