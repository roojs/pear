<?php

/**
 *
 * black list removes all nodes which match and their children.
 *
 * if it's doesnt need to use ':', then we can just do a search.
 *
 */

 
require_once 'Filter.php';

class HTML_Clean_FilterAttribute  extends HTML_Clean_Filter
{
   
 
function __construct($cfg)
    {
        parent::__construct($cfg);
        if (is_array($this->tag) && !in_array(':', $this->tag)) {
            $this->simpleReplace();
            return;
        }
        if (is_string($this->tag) && ':' != $this->tag) {
            $this->simpleReplace();
            return;
        }
        $this->walk($cfg['node']);
    } 
 
    function replaceTag ($n)
    {
        $n->parentNode->removeChild($n);
        return false; // don't both with children..
    }
    
    function simpleReplace()
    {
        foreach(is_array($this->tag) ? $this->tag : array($this->tag) as $t) {
            $ar = $this->fromArray($this->node->getElementsByTagName($t));
            foreach($ar as $k) {
                if ($k->parentNode) {
                    $k->parentNode->removeChild($k);
                }
            }
        }
    }
    
}