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

            if ($pair['key'] == 0){
                if ($pair['value'] == 'ENDSEC') {
                    // End of a section
                    break;
                } elseif ($pair['value'] == 'TABLE'){
                    // Beginning of a table
                    $table = null;
                    continue;
                } elseif ($pair['value'] == 'ENDTAB') {
                    // End of a table
                    $this->addItem($table);
                    continue;
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
                            $table->parse();
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
