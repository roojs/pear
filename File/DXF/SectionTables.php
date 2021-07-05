<?php
class File_DXF_SectionTables extends File_DXF_Section
{
    public function __construct()
    {
        parent::__construct('tables');
    }
    public function parse($handle)
    {
        $table = null;
        $tableName = '';
        require_once 'File/DXF/Table.php';
        require_once 'File/DXF/LType.php';
        require_once 'File/DXF/Style.php';
        require_once 'File/DXF/Layer.php';
        require_once 'File/DXF/AppID.php';
        require_once 'File/DXF/BlockRecord.php';

        while ($pair = $this->readPair($handle)) {
            if ($pair['value'] == 'ENDSEC') {
                break;
            }

            if ($pair['value'] == 'TABLE'){
                $table = null;
                continue;
            }elseif ($pair['value'] == 'ENDTAB') {
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
                    }
                }
            }
        }
    }
}
