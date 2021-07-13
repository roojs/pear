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
                    $this->duplicateRecordCloingFlag = $pair['value'];
                    break;
                case 70:
                    $this->numberOfAttributeDefinitions = $pair['value'];
                    break;
                case 340:
                    $this->hardPointerOfAttributeDefinition = $pair['value'];
                    break;
                case 10:
                    $this->alignmentPointX = $pair['value'];
                    break;
                case 20:
                    $this->insertionPointY = $pair['value'];
                    break;
                case 30:
                    $this->insertionPointZ = $pair['value'];
                    break;
                case 40:
                    $this->annotationScale = $pair['value'];
                    break;
                case 2:
                    $this->attributeDefinitionTagString = $pair['value'];
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for subclass AcDbXrecord ($pairString)");
                    break;
            }
        }
    }
}