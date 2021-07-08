<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionTables extends File_DXF_Section
{
    public $name = 'tables';
	 
    public function parse($dxf)
    {
        $table = null;
        $tableName = '';

        while ($pair = $dxf->readPair()) {

            if($pair['key'] == 0) {
                if ($pair['value'] == 'ENDSEC') {
                    // End of the table section
                    break;
                } 
    
                if ($pair['value'] == 'TABLE'){
                    // Beginning of a new table
    
                }
            }

            if ($pair['key'] == 2) {
                if (!isset($table)) {
                    $tableName = $pair['value'];
                    $table = $dxf->factory('Table',$tableName);
                } else {
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
                            $table->parse($dxf);
                            break;
                        default:
                            print_R($tableName);
                            die("ERROR got unknown table name");
                            break;
                    }
                }
            }
        }
    }
}
