<?php 

/**
 * This handles 'td' tags (and puts nice borders on them.)
 */

 
require_once 'Block.php';
class  HTML_Clean_BlockTd extends HTML_Clean_Block
{
    var $width = ''; // should be a percent.!
    var $textAlign = 'left';
    var $valign = 'top';
    
    var $colspan = 1;
    var $rowspan = 1;
    
    
     
    function __construct($cfg) {
         
        if ($cfg['node']) {
            $this->readElement($cfg['node']);
            $this->updateElement($cfg['node']);
        } 
        parent::__construct();
         
    }
    
    function toObject ()
    {
        $ret = array(
            'tag' => 'td',
            'data-block' => 'Td',
            'valign' => $this->valign,
            'style' => array(
                'text-align' =>  $this->textAlign,
                'border' => 'solid 1px rgb(0, 0, 0)', // ??? hard coded?
                'border-collapse' => 'collapse',
                'padding' => '6px', // 8 for desktop / 4 for mobile
                'vertical-align'=> $this->valign
            ),
            html => $this->html
        );
        if ($this->width != '') {
            $ret->width = $this->width;
            $ret['style']['width'] = $this->width;  
        }
        
        
        if ($this->colspan > 1) {
            $ret['colspan'] = $this->colspan ;
        } 
        if ($this->rowspan > 1) {
            $ret['rowspan'] = $this->rowspan ;
        }
        
           
        
        return $ret;
         
    }
    
    
    function readElement ($node)
    {
        $node  = $node ? $node : $this->node ;
        
        $this->width = $node->getAttribute('width');
        $this->colspan = max(1,1*$node->getAttribute('colspan'));
        $this->rowspan = max(1,1*$node->getAttribute('rowspan'));
        $this->html = $this->innerHTML($node);
        $styles = $this->styleToObject($node,true);
        
        if (!empty($styles['text-align'])) {
            $this->textAlign = $styles['text-align'];
        }
        if ($node->hasAttribute('valign')) {
            $this->valign = $node->getAttribute('valign');
        }
        
    }
    
}