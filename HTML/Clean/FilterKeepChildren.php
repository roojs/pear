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
            if (!$this->isTagMatch($t)) {
                continue;
            }
            $this->replaceTag($t); // this effetively walks all the children.
        }
        $ar = $this->arrayFrom($node->childNodes);
        foreach($ar as $t) {
         
            $node->removeChild($t);
            // what if we need to walk these???
            $node->parentNode->insertBefore($t, $node);
            //if (this.tag !== false) { << cant see why wee need to walk again.
            //    this.walk(ar[i]);
            //    
            //}
        }
        //Roo.log("REMOVE:" + node.tagName);
        node.parentNode.removeChild(node);
        return false; // don't walk children
        
        
        $n->parentNode->removeChild($n);
        return false; // don't both with children..
    }
    
     
    
}