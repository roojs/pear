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
    
    
}