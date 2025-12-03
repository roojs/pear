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

            if(in_array(strtoupper($a->name), $this->attrib_clean)) {
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
        }

        foreach($removeAttributes as $a) {
            $node->removeAttribute($a->name);
        }

        return true;
        */
        foreach($ats as $a) {
                
            if ($a->name == 'style') {
                $this->cleanStyle($node);
                continue;
            }
            /// clean up MS crap..
            // tecnically this should be a list of valid class'es..
            
            
            if ($a->name == 'class') {
                if (preg_match('/^Mso/', $a->value)) {
                    $node->removeAttribute('class');
                    continue;
                }
                if (preg_match('/^body$/', $a->value)) {
                    $node->removeAttribute('class');
                    continue;
                }
            }
            
            
            // style cleanup!?
            // class cleanup?
            
        }
        return true; // clean children
    }
    // cleans urls...
    function cleanAttr($node, $n,$v)
    {
        // starts with 'dot' or 'slash', 'hash' or '{' << template
        if (preg_match('/^(\.|\/|#|\{)/' , $v)) {
            return;
        }
        // standard stuff? - should we allow data?
        if (preg_match('/(http|https|mailto|ftp|data):/' , $v)) {
            return;
        }
        
//            Roo.log("(REMOVE TAG)"+ node.tagName +'.' + n + '=' + v);
        $node->removeAttribute($n);
        
    }
    
    function cleanStyle ($node)
    {
        if (preg_match('/expression/', $node->getAttribute('style'))) { //XSS?? should we even bother..
            $node->removeAttribute('style');
            return;
        }
        $style = $this->styleToObject($node);
        $update = false;
        foreach($style as $k=>$v) {
            
            if ( in_array(strtolower($k), $this->style_black)) {
                unset($style[$k]);
                $update = true;
                continue;
            }
            
            //Roo.log()
            // only allow 'c whitelisted system attributes'
            if ( count($this->style_white) &&  in_array(strtolower($k), $this->style_white)) {
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