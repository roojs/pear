<?php



/**
 *  replaces styles with HTML
 *  
 * 
 *  
 *
 */ 

require_once 'Filter.php';

class HTML_Clean_FilterStyleToTag extends HTML_Clean_Filter
{
    
    var $tag = true;
    
    // what we are going to change..
    var $tags = array(
        'B' => array(
            'font-weight',
            'bold'
        ),
        'I' => array(
            'font-style',
            'italic'
        ),
        'SUP' => array(
            'vertical-align',
            'super'
        ),
        'SUB' => array(
            'vertical-align',
            'sub'
        )
    );
    
    function __construct($cfg)
    {
        parent::__construct($cfg);
        $this->walk($this->node);
    }
    
 
    
    
    
    function replaceTag($node)
    {
        $style = $node->getAttribute('style');

        // no attribute 'style'
        if(empty($style)) {
            return true;
        }

        $tags = array();

        foreach($this->tags as $tag => $s) {
            $pattern = '/' . $s[0] . '\s*:\s*' . $s[1] . '\s*;/';

            $matches = array();

            preg_match($pattern, $style, $matches);

            if(!empty($matches)) {
                // tags to add
                $tags[] = $tag;

                var_dump("REPLACE $style");

                // remove styles
                $style = preg_replace($pattern, '', $style);
            }
        }

        if(empty($tags)){
            return true;
        }

        $node->setAttribute('style', $style);
        
        // copy of child nodes
        $childNodes = array();

        foreach($node->childNodes as $n) {
            $childNodes[] = $n;
        }

        $current = $node;

        // add tags
        foreach($tags as $tag) {
            $new = $node->ownerDocument->createElement($tag);
            $current->append($new);
            $current = $new;
        }

        // move children
        foreach($childNodes as $n) {
            $node->removeChild($n);
            $new->append($n);
        }

        return true;
    }
    
 
    
}