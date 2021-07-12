<?php
class File_DXF_SectionObjects extends File_DXF_Section
{

    public $name = "objects";

    function parse ($dxf) {
        $this->skipParseSection($dxf);
    }

}
