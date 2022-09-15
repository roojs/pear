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

class HTML_Clean_FilterAttribute  extends HTML_Clean_Filter
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
        $this->walk($cfg['node']);
    } 
    
     function replaceTag ($node)
    {
        if (!$node->hasAttributes()) {
            return true; // do children.
        }
        $ats = $this->arrayFrom($node->attributes);
        foreach($ats as $a) {
            
            // remove all if we have a white list..
            if (count($this->attrib_white) && array_search(strtolower($a->name), $this->attrib_white) !== false) {
                $node->removeAttribute($a->name);
                continue;
            }
            
            // always remove 'on'
            if (substr(strtolower($a->name),0,2) == 'on')  {
                $node->removeAttribute($a->name);
                continue;
            }
            
            
            if (array_search( strtolower($a->name),$this->attrib_black) !== false) {
                $node->removeAttribute($a->name);
                continue;
            }
            if (array_search( strtolower($a->name),$this->attrib_clean) !== false)  {
                $this->cleanAttr($node,$a->name,$a->value); // fixme..
                continue;
            }
                
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
            
            if ( array_search(strtolower($k), $this->style_black) !== false) {
                unset($style[$k]);
                $update = true;
                continue;
            }
            
            //Roo.log()
            // only allow 'c whitelisted system attributes'
            if ( count($this->style_white) &&  array_search(strtolower($k), $this->style_white) !== false) {
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