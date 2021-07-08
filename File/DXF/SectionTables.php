<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionTables extends File_DXF_Section
{
    public $name = 'tables';
	 
    public function parse($dxf)
    {
        while ($pair = $dxf->readPair()) {

            if($pair['key'] == 0) {
                if ($pair['value'] == 'ENDSEC') {
                    // End of the tables section
                    break;
                } elseif ($pair['value'] == 'TABLE'){
                    // Beginning of a new table
                    continue;
                } else {
                    // Got invalid tag with the tables section
                    print_r($pair);
                    die('ERROR got invalid tag with the tables section');
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
