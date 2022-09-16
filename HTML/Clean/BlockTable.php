<?php 

/**
 * This handles 'table' tags (and puts nice borders on them.)
 */

 
require_once 'Block.php';
class  HTML_Clean_BlockTable extends HTML_Clean_Block
{
    
    var $rows = array(); // row cols {array}
    var $no_col = 1;
    var $no_row = 1;
    var $width = '100%';
    
    function __construct($cfg) {
        if ($cfg['node']) {
            $this->readElement($cfg['node']);
            $this->updateElement($cfg['node']);
        } 
        parent::__construct();
        if (!$this->node) {
        
        for($r = 0; $r < $this->no_row; $r++) {
            $this->rows[$r] = array();
            for($c = 0; $c < $this->no_col; $c++) {
                $this->rows[$r][$c] = $this->emptyCell();
            }
        }
    }
    
    
    
     