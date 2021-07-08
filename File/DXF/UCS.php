<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_UCS extends File_DXF_BasicObject
{
    
    public $name;
    public $data = array();

    function parse($dxf) {

        while($pair = $dxf->readPair()) {
            if ($pair['key'] == 0) {
                // End of this table entry
                $dxf->pushPair($pair);
                return;
            }
            if ($pair['key'] == 2) {
                $this->name = $pair['value'];
                continue;
            }
            $this->data[$pair['key']] = $pair['value'];
        }
    }
}
