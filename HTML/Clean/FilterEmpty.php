<?php

/**
 *
 * remove empty node for certain tags
 *
 */

 
require_once 'Filter.php';

class HTML_Clean_FilterEmpty extends HTML_Clean_Filter
{
   
 
    function __construct($cfg)
    {
        parent::__construct($cfg);
        $this->walk($this->node);
    }

    function replaceTag($node)
    {
        // children is always walked before the parent
        // as the parent may be removed
        $this->walk($n);

        // only filter empty leaf element
        if(
            count($node->attributes)
            ||
            $node->hasChildNodes()
        ) {
            return false; // dont walk
        }

        $node->parentNode->removeChild($node);

        return false; // dont walk
    }
}