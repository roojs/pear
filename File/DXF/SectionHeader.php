<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionHeader extends File_DXF_Section
{
    public $name = 'header';

    function parse ($dxf) {
        $this->skipParseSection($dxf);
    }
}
