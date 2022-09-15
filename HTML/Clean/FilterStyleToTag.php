<?php



/**
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
        
        
        'B'  => array( 'fontWeight' => 'bold' ),
        'I' =>   array(  'fontStyle'  => 'italic' ),
        
        // h1.. h6 ?? font-size?
        'SUP'  => array(   'verticalAlign'  => 'super'),
        'SUB' => array(   'verticalAlign' => 'sub' )
        
    );
    
    function __construct($cfg)
    {
        foreach($cfg as $k=>$v) {
            $this->$k = $v;
        }
        $this->walk($cfg['node']);
    }
    
 
    
    
    
    function replaceTag($node)
    {
        
        
        if (!$node->hasAttribute("style")) {
            return true;
        }
        $inject = array();
        $style = $this->styleToObject($node);
        foreach ($this->tags as $tn => $kv) {
            list($k,$v) = $kv;
            if (!isset($style[$k]) || $style[$k] != $v) {
                continue;
            }
            unset($style[$k]);
            $inject[] = $tn;
        }
        if (!count($inject) {
            return true; 
        }
        $this->nodeSetStyle($node, $style);
        var cn = Array.from(node.childNodes);
        var nn = node;
        Roo.each(inject, function(t) {
            var nc = node.ownerDocument.createElement(t);
            nn.appendChild(nc);
            nn = nc;
        });
        for(var i = 0;i < cn.length;cn++) {
            node.removeChild(cn[i]);
            nn.appendChild(cn[i]);
        }
        return true /// iterate thru
    }
    
})
    
}