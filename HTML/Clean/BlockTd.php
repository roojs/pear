<?php 

/**
 * This handles 'td' tags (and puts nice borders on them.)
 */

 
require_once 'Block.php';
class  HTML_Clean_BlockTd extends HTML_Clean_Block
{
    
     
    function __construct($cfg) {
         
        if ($cfg['node']) {
            $this->readElement($cfg['node']);
            $this->updateElement($cfg['node']);
        } 
        parent::__construct();
         
    }
    