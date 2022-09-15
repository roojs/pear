<?php

/**
 * Does a few very specific word paste filtering - doc bullents an a name= tags.
 * 
 * doesnt really use filter parent much
 *
 */ 

require_once 'Filter.php';

class HTML_Clean_FilterWord extends HTML_Clean_Filter
{
    
    var $tag = true;
    
    function __construct($cfg)
    {
        $this->replaceDocBullets($cfg->node);
        $this->replaceAname($cfg->node);
    }
   
    
    function replaceAname  ($doc)
    {
        // replace all the a/name without..
        $aa= $this->arrayFrom($doc->getElementsByTagName('a'));
        
        for ($i = 0; $i  < count($aa); $i++) {
            $a = $aa[$i];
            if ($a->hasAttribute("name")) {
                $a->removeAttribute("name");
            }
            if ($a->hasAttribute("href")) {
                continue;
            }
            // reparent children.
            $this->removeNodeKeepChildren($a);
            
        }
        
        
        
    }
    
    
    
}
