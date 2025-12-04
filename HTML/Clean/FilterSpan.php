<?php

/**
 *
 * remove spans without attributes.
 * 
 *
 */

 
require_once 'Filter.php';

class HTML_Clean_FilterSpan extends HTML_Clean_Filter
{
   
 
    function __construct($cfg)
    {
        parent::__construct($cfg);
        $ar = $this->arrayFrom($this->node->getElementsByTagName('span'));
        foreach($ar as $a) {
            if ($a->hasAttributes()) {
                continue;
            }
            $this->removeNodeKeepChildren($a);
        }
        
    }
    
    
}
 