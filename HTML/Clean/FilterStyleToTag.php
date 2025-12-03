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

            var_dump($pattern);
            var_dump($style);
            die('test');

            $matches = array();

            preg_match($pattern, $style, $matches);

            if(!empty($matches)) {
                // tags to add
                $tags[] = $tag;

                // remove styles
                $style = preg_replace($pattern, '', $style);
            }
        }

        if(empty($tags)){
            return;
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
        */
        
        
        if (!$node->hasAttribute("style")) {
            return true;
        }
        $inject = array();
        $style = $this->styleToObject($node, true);
        foreach ($this->tags as $tn => $kv) {
            list($k,$v) = $kv;
            if (!isset($style[$k]) || $style[$k] != $v) {
                continue;
            }
            unset($style[$k]);
            $inject[] = $tn;
        }
        if (!count($inject)) {
            return true; 
        }
        $this->nodeSetStyle($node, $style);
        $cn = $this->arrayFrom($node->childNodes);
        $nn = $node;
        foreach($inject as $t) { 
        
            $nc = $node->ownerDocument->createElement($t);
            $nn->appendChild($nc);
            $nn = $nc;
        }
        foreach($cn as $n) {
            $node->removeChild($n);
            $nn->appendChild($n);
        }
        
        return true; /// iterate thru
    }
    
 
    
}