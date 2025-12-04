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
    var $lang = 'en';
 
    function __construct($cfg)
    {
        parent::__construct($cfg);
        $pp = $this->arrayFrom($this->node->getElementsByTagName('p'));
        foreach($pp as $p) {
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
            $node->parentNode->replaceChild($node->ownerDocument->createElement('br'), $node);
            return false;
        }

        $documentDir = in_array($this->lang, ['ar', 'he', 'fa', 'ur', 'ps', 'syr', 'dv', 'arc', 'nqo', 'sam', 'tzm', 'ug', 'yi']) ? 'rtl' : 'ltr';
        $nodeDir = $node->hasAttribute('dir') ? strtolower($node->getAttribute('dir')) : false;
        $span = $node->ownerDocument->createElement('span');

        // remove p tag but keep children
        // e.g. '<p><b>abc</b></p>' to '<b>abc</b>'
        $ar = $this->arrayFrom($node->childNodes);
        foreach($ar as $a) {
            $node->removeChild($a);

            // copy content to span with if the direction is needed
            if($nodeDir && $nodeDir != $documentDir) {
                $span->appendChild($a);
                continue;
            }

            $node->parentNode->insertBefore($a, $node);
        }

        if($nodeDir && $nodeDir != $documentDir) {
            // keep direction
            $span->setAttribute('dir', $nodeDir);
            $node->parentNode->insertBefore($span, $node);
        }
        
        // double BR.
        $node->parentNode->insertBefore($node->ownerDocument->createElement('br'), $node);
        $node->parentNode->insertBefore($node->ownerDocument->createElement('br'), $node);
        
        $node->parentNode->removeChild($node);
        
    }
}
 