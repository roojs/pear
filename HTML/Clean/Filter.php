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
        for ($i = 0; $i < count($ar); $i++) {
         
            $node->removeChild($ar[$i]);
            // what if we need to walk these???
            $node->parentNode->insertBefore($ar[$i], $node);
           
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
         while($from->childNodes->length) {
            $n = $from->childeNodes->item(i);
            $from->removeChild($n);
            $to->appendChild($n);
        }
        
    }
}
