<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionTables extends File_DXF_Section
{
    public $name = 'tables';

    function parse($dxf) {
        $this->skipParseSection($dxf);
    }
	
    /*
    public function parse($dxf)
    {
        while ($pair = $dxf->readPair()) {

            if($pair['key'] == 0) {
                if ($pair['value'] == 'ENDSEC') {
                    // End of the tables section
                    return;
                } 
                if ($pair['value'] == 'TABLE'){
                    // Beginning of a new table
                    continue;
                } 
            }

            if ($pair['key'] == 2) {
                $tableName = $pair['value'];

                switch ($tableName) {
                    case 'LTYPE':
                    case 'STYLE':
                    case 'LAYER':
                    case 'APPID':
                    case 'BLOCK_RECORD':
                    case 'DIMSTYLE':
                    case 'UCS':
                    case 'VIEW':
                    case 'VPORT':
                        $table = $dxf->factory('Table', array('name' => $tableName));
                        $table->parse($dxf);
         ENDSEC')             break;
                    
                }
            }
        }
    }
    */
}
