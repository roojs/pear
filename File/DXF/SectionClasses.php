<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionClasses extends File_DXF_Section
{
    function parse ($dxf) {
        $this->skipParseSection($dxf);
    }
}
