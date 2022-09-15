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
            if (!$p->parentNode) { // already done?
                continue;
            }
            $this->replaceIt($p);
        }
    }
    
    function replaceIt($node)
    {
        
        if (!$node->previousSibling) { // not hing before us...
            return false;
        }
        
        $ps = $node->nextSibling;
        // find the nex sibling that is a node, 
        while ($ps && $ps->nodeType == 3 && strlen(trim($ps->nodeValue))) {
            $ps = $ps->nextSibling;
        }
        // we have no next sibling, and are inside one of these tags
        if (!$ps &&  in_array(strtoupper($node->parentNode->tagName), array( 'TD', 'TH', 'LI', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6' ))) {
            $node->parentNode->removeChild($node); // remove last BR inside one fo these tags
            return false;
        }
        
        if (!$ps || $ps->nodeType != 1) {
            return;
        }
        // next node node a BR.
        
        if (!$ps || $ps->tagName != 'BR') {
            return; 
        }
        
        
        $ps = $node->previousSibling;
        
        while (ps && ps.nodeType == 3 && ps.nodeValue.trim().length < 1) {
            ps = ps.previousSibling;
        }
        if (!ps || ps.nodeType != 1) {
            return false;
        }
        // if header or BR before.. then it's a candidate for removal.. - as we only want '2' of these..
        if (!ps || [ 'BR', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6' ].indexOf(ps.tagName) < 0) {
            return false;
        }
        
        node.parentNode.removeChild(node); // remove me...
        
        return false; // no need to do children
        
    }
}
 