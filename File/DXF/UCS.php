<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_UCS extends File_DXF_BasicObject
{
    
    function parse($dxf) {
        $this->skipParseTableEntry($dxf);
    }
    
}
