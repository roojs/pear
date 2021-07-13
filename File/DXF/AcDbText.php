<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbText extends File_DXF_Subclass
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
                case 39:
                    $entity->thickness = $pair['value'];
                    break;
                case 10:
                    $entity->textStartPointX = $pair['value'];
                    break;
                case 20:
                    $entity->textStartPointY = $pair['value'];
                    break;
                case 30:
                    $entity->textStartPointZ = $pair['value'];
                    break;
                case 40:
                    $entity->textHeight = $pair['value'];
                    break;
                case 1:
                    $entity->value = $pair['value'];
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
                case 73:
                    $entity->verticalTextJustificationType = $pair['value'];
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for subclass AcDbText ($pairString)");
                    break;
            }
        }
    }
}