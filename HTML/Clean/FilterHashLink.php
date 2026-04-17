<?php

/**
 *
 * remove hash link but keep children
 *
 */

 
require_once 'Filter.php';

class HTML_Clean_FilterHashLink extends HTML_Clean_Filter
{
   
 
    function __construct($cfg)
    {
        parent::__construct($cfg);
        $ar = $this->arrayFrom($this->node->getElementsByTagName('a'));
        foreach($ar as $a) {
            if($a->getAttribute('href') && substr($a->getAttribute('href'), 0, 1) == '#') {
                $this->removeNodeKeepChildren($a);
            }
        }
    }
}