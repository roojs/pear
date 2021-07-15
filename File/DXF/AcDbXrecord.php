<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbXrecord extends File_DXF_Subclass
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
                case 280:
                    $entity->duplicateRecordCloingFlag = $pair['value'];
                    break;
                case 70:
                    $entity->numberOfAttributeDefinitions = $pair['value'];
                    break;
                case 340:
                    $entity->hardPointerOfAttributeDefinition = $pair['value'];
                    break;
                case 10:
                    $entity->alignmentPointX = $pair['value'];
                    break;
                case 20:
                    $entity->insertionPointY = $pair['value'];
                    break;
                case 30:
                    $entity->insertionPointZ = $pair['value'];
                    break;
                case 40:
                    $entity->annotationScale = $pair['value'];
                    break;
                case 2:
                    $entity->attributeDefinitionTagString = $pair['value'];
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for subclass AcDbXrecord ($pairString)");
                    break;
            }
        }
    }
}