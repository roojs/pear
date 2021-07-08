<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionClasses extends File_DXF_Section
{

    public $name = 'classes';
	
    function parse ($dxf) {
        while ($pair = $dxf->readPair()){
            if ($pair['key'] == 0 && $pair['value'] == 'ENDSEC') {
                // End of the classes section
                return;
            }
        }
    }
}
