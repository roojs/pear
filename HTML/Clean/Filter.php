<?php

/**
 * Base class for all the filtering, contains a few usefull routines along with the walk code
 * 
 * doesnt really use filter parent much
 *
 */ 

class  HTML_Clean_Filter
{
    
    
    
    
    removeNodeKeepChildren : function( node)
    {
    
        ar = Array.from(node.childNodes);
        for (var i = 0; i < ar.length; i++) {
         
            node.removeChild(ar[i]);
            // what if we need to walk these???
            node.parentNode.insertBefore(ar[i], node);
           
        }
        node.parentNode.removeChild(node);
    }
    
    listToArray($list)
    {
        $ret = array();
        foreach($list as $l) {
            $ret[] = $l;
        }
        return $ret;
    }
}
