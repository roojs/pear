<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionHeader extends File_DXF_Section
{
    public $name = 'header';

    function parse ($dxf) {
        while ($pair = $dxf->readPair()){
            if ($pair['key'] == 0 && $pair['value'] == 'ENDSEC') {
                // End of the header section
                return;
            }
            if ($pair['key'] == 9) {
                // Beginning of a new header variable
                $variable = $dxf->factory('SystemVariable',
                    array(
                        'name' => $pair['value'],
                        'data' => array(),
                    ),
                );
                $variable->parse();
            }

        }
    }
}
