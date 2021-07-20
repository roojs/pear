<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbBlockReference extends File_DXF_Subclass
{
    
    function parseToEntity($dxf, $entity)
    {
        
        while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:
                case 100:
                case 1001:
                    // End of this subclass
                    $dxf->pushPair($pair);
                    return;
                case 66:
                    $entity->hasAttribute = $pair['value'];
                    break;
                case 2:
                    $entity->blockName = $pair['value'];
                    break;
                case 10:
                    $entity->insertionPointX = $pair['value'];
                    break;
                case 20:
                    $entity->insertionPointY = $pair['value'];
                    break;
                case 30:
                    $entity->insertionPointZ = $pair['value'];
                    break;
                case 41:
                    $entity->scaleX = $pair['value'];
                    break;
                case 42:
                    $entity->scaleY = $pair['value'];
                    break;
                case 43:
                    $entity->scaleZ = $pair['value'];
                    break;
                case 50:
                    $entity->rotation = $pair['value'];
                    break;
                case 70:
                    $entity->columnCount = $pair['value'];
                    break;
                case 71:
                    $entity->rowCount = $pair['value'];
                    break;
                case 44:
                    $entity->columnSpacing = $pair['value'];
                    break;
                case 45:
                    $entity->rowSpacing = $pair['value'];
                    break;
                case 210:
                    $entity->extrusionDirectionX = $pair['value'];
                    break;
                case 220:
                    $entity->extrusionDirectionY = $pair['value'];
                    break;
                case 230:
                    $entity->extrusionDirectionZ = $pair['value'];
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for subclass AcDbBlockReference ($pairString)");
                    break;
            }
        }
    }
}