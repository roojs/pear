<?php



/**
 *  replaces bad attributes... and attribute values
 *  
 *  done by walking all elements
 *   
 *  
 *
 */ 

require_once 'Filter.php';

class HTML_Clean_FilterAttributes  extends HTML_Clean_Filter
{
   
    var $tag =  true; // all tags
    
    var $attrib_black = array(); // array
    var $attrib_clean = array(); // array
    var $attrib_white = array(); // array
    
    var $style_black = array(); // array
    var $style_white = array(); // array

    var $lang = 'en';
    
    function __construct($cfg)
    {
        parent::__construct($cfg);
        $this->walk($this->node);
    } 
    
    function replaceTag ($node)
    {
        // return if no attribute
        if(!count($node->attributes)) {
            return true;
        }

        $removeAttributes = array();

        foreach($node->attributes as $a) {
            if(count($this->attrib_white) && !in_array(strtolower($a->name), $this->attrib_white)) {
                $removeAttributes[] = $a;
                continue;
            }

            // always remove 'on'
            if (substr(strtolower($a->name),0,2) == 'on')  {
                $removeAttributes[] = $a;
                continue;
            }

            if (in_array(strtolower($a->name), $this->attrib_black)) {
                $removeAttributes[] = $a;
                continue;
            }

            if(in_array(strtolower($a->name), $this->attrib_clean)) {
                if(!(
                    preg_match('/^\./', $a->nodeValue) 
                    || 
                    preg_match('/^\//', $a->nodeValue)
                    || 
                    preg_match('/^(http|https):\/\//', $a->nodeValue)
                    || 
                    preg_match('/^mailto:/', $a->nodeValue)
                    || 
                    preg_match('/^ftp:/', $a->nodeValue)
                    || 
                    preg_match('/^data:/', $a->nodeValue)
                    || 
                    preg_match('/^#/', $a->nodeValue)
                    || 
                    preg_match('/^\{/', $a->nodeValue)
                )) {
                    $removeAttributes[] = $a;
                }
                continue;
            }

            if ($a->name == 'style') {
                $this->cleanStyle($node);
                continue;
            }

            if ($a->name == 'class') {
                if (preg_match('/^Mso/', $a->value)) {
                    $removeAttributes[] = $a;
                    continue;
                }
                if (preg_match('/^body$/', $a->value)) {
                    $removeAttributes[] = $a;
                    continue;
                }
            }

            if($a->name == 'dir') {
                $documentDir = in_array($this->lang, ['ar', 'he', 'fa', 'ur', 'ps', 'syr', 'dv', 'arc', 'nqo', 'sam', 'tzm', 'ug', 'yi']) ? 'rtl' : 'ltr';
                $nodeDir = strtolower($a->value);

                // remove span dir if it is same as the document dir
                if(strtolower($node->tagName) == 'span' && $nodeDir == $documentDir) {
                    $removeAttributes[] = $a;
                }
            }
        }

        foreach($removeAttributes as $a) {
            $node->removeAttribute($a->name);
        }

        return true;
    }
    
    function cleanStyle($node)
    {
        if (preg_match('/expression/', $node->getAttribute('style'))) { //XSS?? should we even bother..
            $node->removeAttribute('style');
            return;
        }
        $style = $this->styleToArray($node);
        $update = false;
        foreach($style as $k=>$v) {
            
            if (in_array(strtolower($k), $this->style_black)) {
                unset($style[$k]);
                $update = true;
                continue;
            }
            
            // only allow 'c whitelisted system attributes'
            if (count($this->style_white) && in_array(strtolower($k), $this->style_white)) {
                continue;
            }
            unset($style[$k]);
            $update = true;
        }

        if ($update) {
            $this->nodeSetStyle($node, $style);
        }
    }
}