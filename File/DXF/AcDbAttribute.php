<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbAttribute extends File_DXF_Subclass
{
    
    public $fieldLength = 0; // 73
    public $textRotation = 0; // 50
    public $scaleX = 1; // 41
    public $obliqueAngle = 0; // 51
    public $textStyleName = "STANDARD"; // 7
    public $textGenerationFlags = 0; // 71
    public $horizontalTextJustificationType = 0; // 72
    public $verticalTextJustificationType = 0; // 74
    public $extrusionDirectionX = 0; // 210
    public $extrusionDirectionY = 0; // 220
    public $extrusionDirectionZ = 1; // 230

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
                case 2:
                    $entity->attributeTag = $pair['value'];
                    break;
                case 70:
                    $entity->attributeFlags = $pair['value'];
                    break;
                case 73:
                    $entity->fieldLength = $pair['value'];
                    break;
                case 50:
                    $entity->textRotation = $pair['value'];
                    break;
                case 41:
                    $entity->scaleX = $pair['value'];
                    break;
                case 51:
                    $entity->obliqueAngle = $pair['value'];
                    break;
                case 7:
                    $entity->textStyleName = $pair['value'];
                    break;
                case 71:
                    $entity->textGenerationFlags = $pair['value'];
                    break;
                case 72:
                    $entity->horizontalTextJustificationType = $pair['value'];
                    break;
                case 74:
                    $entity->verticalTextJustificationType = $pair['value'];
                    break;
                case 11:
                    $entity->alignmentPointX = $pair['value'];
                    break;
                case 21:
                    $entity->alignmentPointY = $pair['value'];
                    break;
                case 31:
                    $entity->alignmentPointZ = $pair['value'];
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
                case 280:
                    $entity->lockPositionFlag = $pair['value'];
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for subclass AcDbAttribute ($pairString)");
                    break;
            }
        }
    }
}