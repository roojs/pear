<?php
class File_DXF_SectionTables extends File_DXF_Section
{
    public function __construct()
    {
        parent::__construct('tables');
    }
    public function parse($values)
    {
        $table = null;
        $tableName = '';
        require_once 'File/DXF/Table.php';
        require_once 'File/DXF/LType.php';
        require_once 'File/DXF/Style.php';
        require_once 'File/DXF/Layer.php';
        require_once 'File/DXF/AppID.php';
        require_once 'File/DXF/BlockRecord.php';

        foreach ($values as $value) {
            if ($value['key'] == 0) {
                if ($value['value'] == 'TABLE') {
                    $table = null;
                    continue;
                } elseif ($value['value'] == 'ENDTAB') {
                    $this->tables->addItem($table);
                    continue;
                }
            }
            if ($value['key'] == 2) {
                if (!isset($table)) {
                    $tableName = $value['value'];
                    $table = new File_DXF_Table($tableName);
                } else {
                    switch ($tableName) {
                        case 'LTYPE':
                            $table->addEntry(new File_DXF_LType($value['value']));
                            break;
                        case 'STYLE':
                            $table->addEntry(new File_DXF_Style($value['value']));
                            break;
                        case 'LAYER':
                            $table->addEntry(new File_DXF_Layer($value['value']));
                            break;
                        case 'APPID':
                            $table->addEntry(new File_DXF_AppID($value['value']));
                            break;
                        case 'BLOCK_RECORD':
                            $table->addEntry(new File_DXF_BlockRecord($value['value']));
                            break;
                    }
                }
            }
        }
    }
}
