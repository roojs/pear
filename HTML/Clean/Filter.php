<?php

/**
 * Base class for all the filtering, contains a few usefull routines along with the walk code
 * 
 * doesnt really use filter parent much
 *
 */ 

class  HTML_Clean_Filter
{
    
    
    
    
    function removeNodeKeepChildren  ( $node)
    {
    
        $ar = $this->arrayFrom($node->childNodes);
        foreach($ar as $n) {
            $node->removeChild($n);
            $node->parentNode->insertBefore($n, $node);
           
        }
        $node->parentNode->removeChild($node);
    }
    
    function arrayFrom($list)
    {
        $ret = array();
        foreach($list as $l) {
            $ret[] = $l;
        }
        return $ret;
    }
    function copyInnerHTML($from, $to)
    {
        $ar = $this->arrayFrom($from->childNodes);
        foreach($ar as $n) {
            $from->removeChild($n);
            $to->appendChild($n);
        }
    }
}
