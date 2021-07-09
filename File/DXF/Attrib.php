<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Attrib extends File_DXF_Entity
{

    function parse($dxf)
    {
        // parse common pair for entities
        $this->parseCommon($dxf);
        
        while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:
                    // End of this entity
                    $dxf->pushPair();
                    return;
                default:
                    $groupCode = $pair['key'];
                    throw new Exception ("Got unknown group code ($groupCode)");
                    break;
            }
        }
    }
}
