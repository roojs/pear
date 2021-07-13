<?php

require_once 'File/DXF/Entity.php';

class File_DXF_MText extends File_DXF_Entity 
{
    // For subclass AcDbMText
    public $extrusionDirectionX = 0; // 210
    public $extrusionDirectionY = 0; // 220
    public $extrusionDirectionZ = 1; // 230

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
                case 100:
                    // Beginning of a subclass
                    $dxf->factory($pair['value'])->parseToEntity($dxf, $this);
                    break;
                case 1001:
                    $this->skipParseExtendedData($dxf);
                    break;
                default:
                    $groupCode = $pair['key'];
                    throw new Exception ("Got unknown group code for entity MTEXT ($groupCode)");
                    break;
            }
    
        }
    }
}