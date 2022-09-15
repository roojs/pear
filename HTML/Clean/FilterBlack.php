<?php

/**
 *
 * black list removes all nodes which match and their children.
 *
 * if it's doesnt need to use ':', then we can just do a search.
 *
 */

 
require_once 'Filter.php';

class HTML_Clean_FilterAttribute  extends HTML_Clean_Filter
{
   
 
function __construct($cfg)
    {
        parent::__construct($cfg);
        if (is_array($this->tag) && !in_array(':', $this->tag)) {
            $this->simpleReplace();
            return;
        }
        if (is_string($this->tag) && ':' != $this->tag) {
            $this->simpleReplace();
            return;
        }
        $this->walk($cfg['node']);
    } 

Roo.extend(Roo.htmleditor.FilterBlack, Roo.htmleditor.Filter,
{
    tag : true, // all elements.
   
    replaceTag : function(n)
    {
        n.parentNode.removeChild(n);
    }
});