<?php

class File_DXF_SectionTables extends File_DXF_Section
{
    public $name = 'tables';

    /**
	 *
	 * TODO ENHANCE / CHECK THE CODE BLOEW
	 *
	 */
	 
    public function parse($dxf)
    {
        $table = null;
        $tableName = '';
        
        require_once 'File/DXF/Table.php';
        require_once 'File/DXF/LType.php';
        require_once 'File/DXF/Style.php';
        require_once 'File/DXF/Layer.php';
        require_once 'File/DXF/AppID.php';
        require_once 'File/DXF/BlockRecord.php';
        require_once 'File/DXF/DimStyle.php';
        require_once 'File/DXF/UCS.php';
        require_once 'File/DXF/View.php';
        require_once 'File/DXF/VPort.php';

        while ($pair = $this->readPair()) {
            if ($pair['value'] == 'ENDSEC') {
                // End of a section
                break;
            }

            if ($pair['value'] == 'TABLE'){
                // Beginning of a table
                $table = null;
                continue;
                
            } elseif ($pair['value'] == 'ENDTAB') {
                // End of a table
                $this->addItem($table);
                continue;
            }

            if ($pair['key'] == 2) {
                if (!isset($table)) {
                    $tableName = $pair['value'];
                    $table = new File_DXF_Table($tableName);
                } else {
                    switch ($tableName) {
                        case 'LTYPE':
                            $table->addEntry(new File_DXF_LType($pair['value']));
                            break;
                        case 'STYLE':
                            $table->addEntry(new File_DXF_Style($pair['value']));
                            break;
                        case 'LAYER':
                            $table->addEntry(new File_DXF_Layer($pair['value']));
                            break;
                        case 'APPID':
                            $table->addEntry(new File_DXF_AppID($pair['value']));
                            break;
                        case 'BLOCK_RECORD':
                            $table->addEntry(new File_DXF_BlockRecord($pair['value']));
                            break;
                        case 'DIMSTYLE':
                            $table->addEntry(new File_DXF_DimStyle($pair['value']));
                            break;
                        case 'UCS':
                            $table->addEntry(new File_DXF_UCS($pair['value']));
                            break;
                        case 'VIEW':
                            $table->addEntry(new File_DXF_View($pair['value']));
                            break;
                        case 'VPORT':
                            $table->addEntry(new File_DXF_VPort($pair['value']));
                            break;
                    }
                }
            }
        }
    }
}
