<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionBlocks extends File_DXF_Section
{

	public $name = 'blocks';
	 
    function parse($dxf) {
		$this->skipParseSection($dxf);
	}

}
