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
    static $counter = 0;
    function __construct($cfg)
    {
        parent::__construct($cfg);
        if ($this->tag === false) { //not sure why.
            return;
        }
        
        $this->walk($this->node);
    } 
 
    function replaceTag ($n)
    {
        // children is always walked before the parent
        // as the parent may be removed
        $this->walk($n);

        $this->removeNodeKeepChildren($n);
        return false; // don't walk children
        
    }
    
     
    
}