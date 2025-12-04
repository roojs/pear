<?php

/**
 *
 *  br br br >>> BR ?
 *  
 *
 */

 
require_once 'Filter.php';

class HTML_Clean_FilterLongBR extends HTML_Clean_Filter
{
   
 
    function __construct($cfg)
    {
        parent::__construct($cfg);
        $pp = $this->arrayFrom($this->node->getElementsByTagName('br'));
        foreach($pp as $p) {
            if (!$p->parentNode) { // should not happen as we only walk forwards.
                continue;
            }
            $this->replaceIt($p);
        }
    }
    
    function replaceIt($node)
    {
        if (!$node->previousSibling) { // nothing before us...
            return false;
        }


        $ns = $node->nextSibling;
        while(!empty($ns) && $ns->nodeType == 3 && trim($ns->nodeValue) == '') {
            $ns = $ns->nextSibling;
        }

        // remove last br tag inside one of these tags
        if(empty($ns) && in_array(strtoupper($node->parentNode->tagName), array('TD', 'TH', 'LI', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6'))) {
            $node->parentNode->removeChild($node);
            return;
        }

        /*

        // return if the next sibling is not a br tag
        if(empty($ns) || $ns->nodeType != 1 || strtoupper($ns->tagName) != 'BR') {
            return;
        }

        $ps = $node->previousSibling;

        while(!empty($ps) && $ps->nodeType == 3 && trim($ps->nodeValue) == '') {
            $ps = $ps->previousSibling;
        }

        // return if the previous sibling is not a br tag or a heading
        if(empty($ps) || $ps->nodeType != 1 || !in_array(strtoupper($ps->tagName), array('BR', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6'))) {
            return;
        }

        $node->parentNode->removeChild($node);
        */
        
        if (!$ps || $ps->nodeType != 1) {
            return;
        }
        // next node node a BR.
        
        if (!$ps || $ps->tagName != 'BR') {
            return; 
        }
        
        
        $ps = $node->previousSibling;
        
        while ($ps && $ps->nodeType == 3 &&  strlen(trim($ps->nodeValue)) < 1) {
            $ps = $ps->previousSibling;
        }
        
        if (!$ps || $ps->nodeType != 1) {
            return;
        }
        // if header or BR before.. then it's a candidate for removal.. - as we only want '2' of these..
        if (!$ps || !in_array(strtoupper($node->parentNode->tagName), array( 'TD', 'TH', 'LI', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6' ))) {
            return;
        }
        
        $node->parentNode->removeChild($node); // remove me...
        
        
    }
}
 