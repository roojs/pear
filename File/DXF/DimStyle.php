<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_DimStyle extends File_DXF_BasicObject
{
    function parse($dxf) {
        $this->skipParseTableEntry($dxf);
    }
}
