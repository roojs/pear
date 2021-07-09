<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionTables extends File_DXF_Section
{
    public $name = 'tables';

    function parse($dxf) {
        $this->skipParseSection($dxf);
    }
    
}
