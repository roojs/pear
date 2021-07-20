<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionThumbnailImage extends File_DXF_Section
{
    function parse ($dxf) {
        $this->skipParseSection($dxf);
    }
}
