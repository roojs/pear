<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbAlignedDimension extends File_DXF_Subclass
{
    
    function parseToEntity($dxf, $entity)
    {
        
        while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:
                case 100:
                case 1001:
                    // End of a subclass
                    $dxf->pushPair($pair);
                    return;
                    break;
                default:
                    break; //.. ignore...
            }
        } 
    }
}