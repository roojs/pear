<?php

/**
 *
 * if the node matches, it will replace the child with children.
 * done for wierd namespaced nodes, and stuff like font.
 *
 * js one extends black?
 *
 */

 
require_once 'Filter.php';

class HTML_Clean_FilterKeepChildren extends HTML_Clean_Filter
{
   
 
    function __construct($cfg)
    {
        parent::__construct($cfg);
        if ($this->tag === false) { //not sure why.
            return;
        }
        
        $this->walk($cfg['node']);
    } 
 
    function replaceTag ($n)
    {
        
          // walk children...
        //Roo.log(node.tagName);
        
        $ar = $this->arrayFrom($node->childNodes);
        
        
        //remove first.. - otherwise due to our walking method - the parent will not look at them.
        foreach($ar as $t) {
            
        for (var i = 0; i < ar.length; i++) {
            var e = ar[i];
            if (e.nodeType == 1) {
                if (
                    (typeof(this.tag) == 'object' && this.tag.indexOf(e.tagName) > -1)
                    || // array and it matches
                    (typeof(this.tag) == 'string' && this.tag == e.tagName)
                    ||
                    (e.tagName.indexOf(":") > -1 && typeof(this.tag) == 'object' && this.tag.indexOf(":") > -1)
                    ||
                    (e.tagName.indexOf(":") > -1 && typeof(this.tag) == 'string' && this.tag == ":")
                ) {
                    this.replaceTag(ar[i]); // child is blacklisted as well...
                    continue;
                }
            }
        }  
        ar = Array.from(node.childNodes);
        for (var i = 0; i < ar.length; i++) {
         
            node.removeChild(ar[i]);
            // what if we need to walk these???
            node.parentNode.insertBefore(ar[i], node);
            if (this.tag !== false) {
                this.walk(ar[i]);
                
            }
        }
        //Roo.log("REMOVE:" + node.tagName);
        node.parentNode.removeChild(node);
        return false; // don't walk children
        
        
        $n->parentNode->removeChild($n);
        return false; // don't both with children..
    }
    
     
    
}