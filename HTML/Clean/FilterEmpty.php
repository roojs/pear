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
        // start from leaf node
        if($node->hasChildNodes()) {
            // copy of child nodes
            $childNodes = array();

            foreach($node->childNodes as $n) {
                $childNodes[] = $n;
            }
            foreach($childNodes as $n) {
                $this->walk($n);
            }
        }

        $tags = array(
            'B', 'I', 'U', 'S'
        );

        // only filter empty leaf element with certain tags
        if(
            !in_array(strtoupper($node->tagName), $tags)
            ||
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