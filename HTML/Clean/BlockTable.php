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
    
    function toObject ()
    {
        
        $ret = array(
            'tag' => 'table',
            'data-block' => 'Table',
            'style' => array(
                'width'=>  $this->width,
                'border' => 'solid 1px #000', // ??? hard coded?
                'border-collapse' => 'collapse' 
            ),
            'cn' => array(
                array( 'tag' => 'tbody' , 'cn' => array() ) 
            )
        );
        
        // do we have a head = not really 
        $ncols = 0;
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
            
            
            // does the row have any properties? ?? height?
            $nc = 0;
            foreach($row as $cell) {
                
                $td = array(
                    'tag' => 'td',
                    'data-block' => 'Td',
                    'html' => $cell->html,
                    'style' => $cell->style
                );
                if ($cell->colspan > 1) {
                    $td->colspan = $cell->colspan ;
                    $nc += $cell->colspan;
                } else {
                    $nc++;
                }
                if ($cell->rowspan > 1) {
                    $td->rowspan = $cell->rowspan ;
                }
                
                
                // widths ?
                $tr->cn[] = $td;
                    
                
            }
            
            $ret->cn[0]->cn[] = $tr;
            
            $ncols = max($nc, $ncols);
            
            
        }
        // add the header row..
        
        $ncols++; // not used?
         
        
        return $ret;
         
    }
    
    function readElement($node)
    {
        $node  = $node ? $node : $this->node ;
        $this->width = this.getVal($node, true, 'style', 'width') || '100%';
        
        this.rows = array();
        $this->no_row = 0;
        $trs = $this->arrayFrom($node->getElementsByTagName('tr'));
        foreach($trs as $tr) {
            $row =  array();
            
            
            $this->no_row++;
            $no_column = 0;
            foreach($node->getElementsByTagName('td') as $td) {
                
                
                $add = new HTML_Clean_Block_Td( array('node' => $td ));
                    /*'colspan : td.hasAttribute('colspan') ? td.getAttribute('colspan')*1 : 1,
                    rowspan : td.hasAttribute('rowspan') ? td.getAttribute('rowspan')*1 : 1,
                    style : td.hasAttribute('style') ? td.getAttribute('style') : '',
                    html : td.innerHTML
                    
                };
                */
                no_column += add.colspan;
                     
                
                row.push(add);
                
                
            },this);
            $this->rows[] = $row;     
            this.no_col = Math.max(this.no_col, no_column);
            
            
        },this);
        
        
    },
    
     emptyCell : function() {
        return (new Roo.htmleditor.BlockTd({})).toObject();
        
     
    },