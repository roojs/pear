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
        $pp = $this->arrayFrom($this->node->getElementsByTagName('p'));
        while($pp->length) {
            $this->replaceIt($p);
        }
    }
    
    function replaceIt($node)
    {
        // replace empty p tag with br
        // e.g. '<p> </p>' to '<br>'
        if(
            count($node->childNodes) == 1 &&
            $node->childNodes->item(0)->nodeType == 3 &&
            trim($node->childNodes->item(0)->textContent) == ''
        ) {
            $node->parentNode->replaceChild($node->ownerDocument->createElement('BR'), $node);
            return false;
        }

        // remove p tag but keep children
        // e.g. '<p><b>abc</b></p>' to '<b>abc</b>'
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
 