<?php

/**
 * Base class for all the filtering, contains a few usefull routines along with the walk code
 * 
 * doesnt really use filter parent much
 *
 */ 

class  HTML_Clean_Filter
{
    var $replaceComment = true; // default to trash these.!
    
    function walk ($dom)
    {
        
        $ar = $this->arrayFrom($dom->childNodes);
        foreach($ar as $e)
        
            switch(true) {
                
                case $e->nodeType == 8 &&  $this->replaceComment  !== false: // comment
                    this.replaceComment(e);
                    return;
                
                case e.nodeType != 1: //not a node.
                    return;
                
                case this.tag === true: // everything
                case e.tagName.indexOf(":") > -1 && typeof(this.tag) == 'object' && this.tag.indexOf(":") > -1:
                case e.tagName.indexOf(":") > -1 && typeof(this.tag) == 'string' && this.tag == ":":
                case typeof(this.tag) == 'object' && this.tag.indexOf(e.tagName) > -1: // array and it matches.
                case typeof(this.tag) == 'string' && this.tag == e.tagName: // array and it matches.
                    if (this.replaceTag && false === this.replaceTag(e)) {
                        return;
                    }
                    if (e.hasChildNodes()) {
                        this.walk(e);
                    }
                    return;
                
                default:    // tags .. that do not match.
                    if (e.hasChildNodes()) {
                        this.walk(e);
                    }
            }
            
        }, this);
        
    },
    
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
        foreach($list as $l) {
            $ret[] = $l;
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
    
    function styleToObject($node)
    {
        $styles = explode(';',$node->hasAttribute("style") ? $node->getAttribute("style")  : '');
        $ret = array();
        foreach($styles as $s) {
            if (strpos($s, ':') === false) {
                return;
            }
            $kv = explode(':', $s, 2);
             
            // what ever is left... we allow.
            $ret[trim($kv[0])] = trim($kv[1]);
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
