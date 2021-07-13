<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbBlockBegin extends File_DXF_Subclass
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
                case 2:
                case 3:
                    $entity->blockName = $pair['value'];
                    break;
                case 70:
                    $entity->blockTypeFlags = $pair['value'];
                    break;
                case 10:
                    $entity->basePointX = $pair['value'];
                    break;
                case 20:
                    $entity->basePointY = $pair['value'];
                    break;
                case 30:
                    $entity->basePointZ = $pair['value'];
                    break;
                case 1:
                    $entity->xRefPathName = $pair['value'];
                    break;
                case 4:
                    $entity->blockDescription = $pair['value'];
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for subclass AcDbBlockBegin ($pairString)");
                    break;
            }
        }
    }
}