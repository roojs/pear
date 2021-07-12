<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionThumbnailImage extends File_DXF_Section
{

    public $name = 'thumbnailImage';

    function parse ($dxf) {
        $this->skipParseSection($dxf);
    }
	 
}
