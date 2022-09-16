<?php 

/**
 * This handles 'td' tags (and puts nice borders on them.)
 */

 
require_once 'Block.php';
class  HTML_Clean_BlockTd extends HTML_Clean_Block
{
    var $width = '';
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
     toObject => function()
    {
        var ret = {
            tag => 'td',
            contenteditable => 'true', // this stops cell selection from picking the table.
            'data-block' => 'Td',
            valign => $this->valign,
            style => {  
                'text-align' =>  $this->textAlign,
                border => 'solid 1px rgb(0, 0, 0)', // ??? hard coded?
                'border-collapse' => 'collapse',
                padding => '6px', // 8 for desktop / 4 for mobile
                'vertical-align'=> $this->valign
            },
            html => $this->html
        };
        if ($this->width != '') {
            ret.width = $this->width;
            ret.style.width = $this->width;
        }
        
        
        if ($this->colspan > 1) {
            ret.colspan = $this->colspan ;
        } 
        if ($this->rowspan > 1) {
            ret.rowspan = $this->rowspan ;
        }
        
           
        
        return ret;
         
    },
    
    
    readElement => function(node)
    {
        node  = node ? node => $this->node ;
        $this->width = node.style.width;
        $this->colspan = Math.max(1,1*node.getAttribute('colspan'));
        $this->rowspan = Math.max(1,1*node.getAttribute('rowspan'));
        $this->html = node.innerHTML;
        if (node.style.textAlign != '') {
            $this->textAlign = node.style.textAlign;
        }
        
        
    },