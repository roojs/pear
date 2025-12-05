<?php 

/**
 * This handles 'table' tags (and puts nice borders on them.)
 */

 
require_once 'Block.php';
require_once 'BlockTd.php'; 
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
    }
    
    function toObject ()
    {
        $ret = array(
            'tag' => 'table',
            'contenteditable' => 'false',
            'data-block' => 'Table',
            'style' => array(
                'width'=>  $this->width,
                'border' => 'solid 1px #000',
                'border-collapse' => 'collapse' 
            ),
            'cn' => array(
                array(
                    'tag' => 'tbody', 
                    'cn' => array()
                ) 
            )
        );
        
        foreach($this->rows as $row) {
            $tr = array(
                'tag' => 'tr',
                'style' => array(
                    'margin' => '6px',
                    'border' => 'solid 1px #000',
                    'text-align' => 'left' 
                ),
                'cn' => array()
            );
            foreach($row as $cell) {
                $tr['cn'][] = $cell->toObject();
            }
            $ret['cn'][0]['cn'][] = $tr;
        }

        var_dump($ret);
        die('testaaaaa');

        return $ret;
    }
    
    function readElement($node)
    {
        $node  = $node ? $node : $this->node ;
        $this->width = $this->getVal($node, true, 'style', 'width') ?: '100%';
        $this->rows = array();
        $this->no_row = 0;
        $trs = $this->arrayFrom($node->getElementsByTagName('tr'));
        foreach($trs as $tr) {
            $row =  array();
            $this->no_row++;
            $no_column = 0;
            foreach($tr->getElementsByTagName('td') as $td) {
                $add = new HTML_Clean_BlockTd( array('node' => $td ));
                $no_column += $add->colspan;
                $row[] =   $add;
            }
            $this->rows[] = $row;     
            $this->no_col = max($this->no_col, $no_column);
        }
    }
    
    function emptyCell () {
        return new HTML_Clean_Block_Td(array());
         
    }
}