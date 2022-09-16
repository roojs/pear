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

class HTML_Clean_FilterParagraph extends HTML_Clean_Filter
{
   
 
    function __construct($cfg)
    {
        parent::__construct($cfg);
        $pp = $this->node->getElementsByTagName('p');
        while($pp->length) {
            $this->replaceIt($p);
        }
    }
    
    function replaceIt($node)
    {
        
        if ($node->childNodes->length == 1 &&
            $node->childNodes->item(0)->nodeType == 3 &&
            strlen(trim($node->childNodes->item(0)->textContent)) < 1
            ) {
            
            // remove and replace with '<BR>';
            $node->parentNode->replaceChild($node->ownerDocument->createElement('BR'),$node);
        }
        $ar = $this->arrayFrom($node->childNodes);
        foreach($ar as $a) {
            $node->removeChild($a);
            // what if we need to walk these???
            $node->parentNode->insertBefore($a, $node);
        }
        // now what about this?
        // <p> &nbsp; </p>
        
        // double BR.
        $node->parentNode->insertBefore($node->ownerDocument->createElement('BR'), $node);
        $node->parentNode->insertBefore($node->ownerDocument->createElement('BR'), $node);
        
        $node->parentNode->removeChild($node);
        
    }
}
 